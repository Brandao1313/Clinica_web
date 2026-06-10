<?php
// ====================================================
// ARQUIVO: backend/controllers/agendamento_controller.php
// Descrição: Processa ações de agendamentos
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar autenticação
require_login();

// Processar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$acao = sanitizar_input($_POST['acao'] ?? '');
$id_cliente = $_SESSION['id_cliente'];
$conexao_db = Conexao::getInstance()->getConexao();
$erros = [];

// ====== AGENDAR CONSULTA ======
if ($acao === 'agendar_consulta') {
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);
    $data_hora = sanitizar_input($_POST['data_hora'] ?? '');
    $notas = sanitizar_input($_POST['notas'] ?? '');

    // Validações
    if ($id_especialidade <= 0) {
        $erros[] = 'Especialidade inválida';
    }

    if (empty($data_hora) || !validar_data_agendamento($data_hora . ':00')) {
        $erros[] = 'Data e hora inválidas (deve ser no futuro)';
    }

    if (!empty($erros)) {
        $_SESSION['erros_agendamento'] = $erros;
        redirect('views/painel_cliente.php?acao=agendar');
    }

    // Converter formato datetime-local para formato MySQL
    $data_hora = str_replace('T', ' ', $data_hora) . ':00';

    // Inserir agendamento
    $stmt = $conexao_db->prepare(
        'INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, data_hora, status, notas)
         VALUES (?, "consulta", ?, ?, "pendente", ?)'
    );

    if ($stmt) {
        $stmt->bind_param('iss', $id_cliente, $id_especialidade, $data_hora, $notas);

        if ($stmt->execute()) {
            set_flash_message('agendamento', SUCESSO_AGENDAMENTO, 'sucesso');
            log_acao('AGENDAR_CONSULTA', $id_cliente, "Especialidade: $id_especialidade, Data: $data_hora");
            redirect('views/painel_cliente.php?acao=agendamentos');
        } else {
            $erros[] = 'Erro ao agendar: ' . $conexao_db->error;
        }
        $stmt->close();
    }

    $_SESSION['erros_agendamento'] = $erros;
    redirect('views/painel_cliente.php?acao=agendar');
}

// ====== AGENDAR EXAME ======
elseif ($acao === 'agendar_exame') {
    $id_exame = intval($_POST['id_exame'] ?? 0);
    $data_hora = sanitizar_input($_POST['data_hora'] ?? '');
    $notas = sanitizar_input($_POST['notas'] ?? '');

    // Validações
    if ($id_exame <= 0) {
        $erros[] = 'Exame inválido';
    }

    if (empty($data_hora) || !validar_data_agendamento($data_hora . ':00')) {
        $erros[] = 'Data e hora inválidas (deve ser no futuro)';
    }

    if (!empty($erros)) {
        $_SESSION['erros_agendamento'] = $erros;
        redirect('views/painel_cliente.php?acao=exames');
    }

    // Converter formato datetime-local para formato MySQL
    $data_hora = str_replace('T', ' ', $data_hora) . ':00';

    // Inserir agendamento
    $stmt = $conexao_db->prepare(
        'INSERT INTO agendamentos (id_cliente, tipo, id_exame, data_hora, status, notas)
         VALUES (?, "exame", ?, ?, "pendente", ?)'
    );

    if ($stmt) {
        $stmt->bind_param('iss', $id_cliente, $id_exame, $data_hora, $notas);

        if ($stmt->execute()) {
            set_flash_message('agendamento', SUCESSO_AGENDAMENTO, 'sucesso');
            log_acao('SOLICITAR_EXAME', $id_cliente, "Exame: $id_exame, Data: $data_hora");
            redirect('views/painel_cliente.php?acao=agendamentos');
        } else {
            $erros[] = 'Erro ao solicitar exame: ' . $conexao_db->error;
        }
        $stmt->close();
    }

    $_SESSION['erros_agendamento'] = $erros;
    redirect('views/painel_cliente.php?acao=exames');
}

// ====== CANCELAR AGENDAMENTO ======
elseif ($acao === 'cancelar_agendamento') {
    $id_agendamento = intval($_POST['id_agendamento'] ?? 0);

    // Verificar se agendamento pertence ao cliente e pode ser cancelado
    $stmt = $conexao_db->prepare('SELECT status FROM agendamentos WHERE id = ? AND id_cliente = ?');
    $stmt->bind_param('ii', $id_agendamento, $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows === 0) {
        $_SESSION['erros_agendamento'] = ['Agendamento não encontrado'];
        redirect('views/painel_cliente.php?acao=agendamentos');
    }

    $agendamento = $resultado->fetch_assoc();

    if (!pode_cancelar_agendamento($agendamento['status'])) {
        $_SESSION['erros_agendamento'] = ['Este agendamento não pode ser cancelado'];
        redirect('views/painel_cliente.php?acao=agendamentos');
    }

    // Cancelar agendamento
    $stmt = $conexao_db->prepare('UPDATE agendamentos SET status = "cancelado" WHERE id = ?');
    $stmt->bind_param('i', $id_agendamento);

    if ($stmt->execute()) {
        set_flash_message('agendamento', SUCESSO_CANCELAMENTO, 'sucesso');
        log_acao('CANCELAR_AGENDAMENTO', $id_cliente, "Agendamento: $id_agendamento");
    }
    $stmt->close();

    redirect('views/painel_cliente.php?acao=agendamentos');
}

// Ação desconhecida
$_SESSION['erros_agendamento'] = ['Ação não permitida'];
redirect('views/painel_cliente.php');

?>
