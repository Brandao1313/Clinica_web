<?php
// ====================================================
// ARQUIVO: backend/controllers/recepcionista_controller.php
// Descrição: Ações da recepcionista (agendamentos)
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_recepcionista();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $_SESSION['erros_recep'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_recepcionista.php');
}

$acao      = sanitizar_input($_POST['acao'] ?? '');
$redirect  = sanitizar_input($_POST['redirect'] ?? 'dashboard');
$conexao_db = Conexao::getInstance()->getConexao();

// ====== CONFIRMAR AGENDAMENTO ======
if ($acao === 'confirmar_agendamento') {
    $id = intval($_POST['id_agendamento'] ?? 0);
    $stmt = $conexao_db->prepare("UPDATE agendamentos SET status='confirmado' WHERE id=? AND status='pendente'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    log_acao('RECEP_CONFIRMAR_AGENDAMENTO', $_SESSION['id_cliente'], "ID: $id");
    set_flash_message('recepcionista', 'Agendamento confirmado com sucesso.', 'sucesso');
    redirect('backend/views/painel_recepcionista.php?acao=' . $redirect);
}

// ====== CANCELAR AGENDAMENTO ======
elseif ($acao === 'cancelar_agendamento') {
    $id = intval($_POST['id_agendamento'] ?? 0);
    $stmt = $conexao_db->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status IN ('pendente','confirmado')");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    log_acao('RECEP_CANCELAR_AGENDAMENTO', $_SESSION['id_cliente'], "ID: $id");
    set_flash_message('recepcionista', 'Agendamento cancelado.', 'sucesso');
    redirect('backend/views/painel_recepcionista.php?acao=' . $redirect);
}

// ====== CONCLUIR AGENDAMENTO ======
elseif ($acao === 'concluir_agendamento') {
    $id = intval($_POST['id_agendamento'] ?? 0);
    $stmt = $conexao_db->prepare("UPDATE agendamentos SET status='concluído', data_realizacao=DATE('now','localtime') WHERE id=? AND status='confirmado'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    log_acao('RECEP_CONCLUIR_AGENDAMENTO', $_SESSION['id_cliente'], "ID: $id");
    set_flash_message('recepcionista', 'Agendamento marcado como concluído.', 'sucesso');
    redirect('backend/views/painel_recepcionista.php?acao=' . $redirect);
}

// ====== CRIAR AGENDAMENTO ======
elseif ($acao === 'criar_agendamento') {
    $id_cliente      = intval($_POST['id_cliente'] ?? 0);
    $tipo            = sanitizar_input($_POST['tipo'] ?? 'consulta');
    $id_medico       = intval($_POST['id_medico'] ?? 0) ?: null;
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0) ?: null;
    $data_hora       = sanitizar_input($_POST['data_hora'] ?? '');
    $notas           = sanitizar_input($_POST['notas'] ?? '');

    $erros = [];

    if ($id_cliente <= 0)       $erros[] = 'Selecione um paciente.';
    if (empty($data_hora))      $erros[] = 'Informe a data e hora.';
    if (!in_array($tipo, ['consulta','exame'])) $erros[] = 'Tipo inválido.';

    if (empty($erros)) {
        // Valor e médico
        $valor = 0;
        if ($id_medico) {
            $stmt = $conexao_db->prepare('SELECT valor_consulta FROM medicos WHERE id=?');
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $valor = $row['valor_consulta'] ?? 0;
            $stmt->close();
        }

        $stmt = $conexao_db->prepare(
            'INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total)
             VALUES (?, ?, ?, ?, ?, "pendente", ?, ?)'
        );
        $stmt->bind_param('isiissd', $id_cliente, $tipo, $id_especialidade, $id_medico, $data_hora, $notas, $valor);

        if ($stmt->execute()) {
            log_acao('RECEP_CRIAR_AGENDAMENTO', $_SESSION['id_cliente'], "Cliente: $id_cliente | $tipo | $data_hora");
            set_flash_message('recepcionista', 'Agendamento criado com sucesso!', 'sucesso');
            redirect('backend/views/painel_recepcionista.php?acao=agendamentos');
        } else {
            $erros[] = 'Erro ao salvar. Tente novamente.';
        }
        $stmt->close();
    }

    $_SESSION['erros_recep'] = $erros;
    redirect('backend/views/painel_recepcionista.php?acao=novo_agendamento');
}

$_SESSION['erros_recep'] = ['Ação não permitida.'];
redirect('backend/views/painel_recepcionista.php');
?>
