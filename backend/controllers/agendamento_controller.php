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

// Verificação CSRF para todas as ações
$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    set_flash_message('agendamento', 'Token de segurança inválido. Tente novamente.', 'erro');
    redirect('backend/views/painel_cliente.php?acao=agendamentos');
}

// ====== AGENDAR CONSULTA ======
if ($acao === 'agendar_consulta') {
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);
    $id_medico = intval($_POST['id_medico'] ?? 0);
    $data = sanitizar_input($_POST['data'] ?? '');
    $horario = sanitizar_input($_POST['horario'] ?? '');
    $notas = sanitizar_input($_POST['notas'] ?? '');

    // Validações básicas
    if ($id_especialidade <= 0) {
        $erros[] = 'Especialidade inválida';
    }

    if ($id_medico <= 0) {
        $erros[] = 'Selecione um médico';
    }

    if (!validar_data($data) || empty($horario) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario)) {
        $erros[] = 'Data e hora inválidas';
    }

    $data_hora = $data . ' ' . $horario . ':00';

    if (empty($erros) && !validar_data_agendamento($data_hora)) {
        $erros[] = 'Data e hora inválidas (deve ser no futuro)';
    }

    // Verificar se o médico existe, está ativo e pertence à especialidade escolhida
    $medico = null;
    if (empty($erros)) {
        $stmt = $conexao_db->prepare(
            'SELECT * FROM medicos WHERE id = ? AND id_especialidade = ? AND ativo = 1'
        );
        $stmt->bind_param('ii', $id_medico, $id_especialidade);
        $stmt->execute();
        $medico = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$medico) {
            $erros[] = 'Médico inválido para a especialidade selecionada';
        }
    }

    // Verificar se o horário escolhido está dentro da grade do médico
    // e ainda não está ocupado por outro agendamento
    if (empty($erros)) {
        $dia_semana = (int) date('w', strtotime($data));

        $stmt = $conexao_db->prepare(
            'SELECT hora_inicio, hora_fim, intervalo_minutos
             FROM horarios_atendimento
             WHERE id_medico = ? AND dia_semana = ? AND ativo = 1'
        );
        $stmt->bind_param('ii', $id_medico, $dia_semana);
        $stmt->execute();
        $janelas = $stmt->get_result()->fetch_all();
        $stmt->close();

        $disponivel = false;
        foreach ($janelas as $janela) {
            $slots = gerar_slots_horario($janela['hora_inicio'], $janela['hora_fim'], $janela['intervalo_minutos']);
            if (in_array($horario, $slots, true)) {
                $disponivel = true;
                break;
            }
        }

        if (!$disponivel) {
            $erros[] = 'O médico não atende neste horário';
        } else {
            // Verificar se já existe agendamento neste horário para este médico
            $stmt = $conexao_db->prepare(
                "SELECT COUNT(*) as total FROM agendamentos
                 WHERE id_medico = ? AND status IN ('pendente', 'confirmado') AND data_hora = ?"
            );
            $stmt->bind_param('is', $id_medico, $data_hora);
            $stmt->execute();
            $ocupado = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            if ($ocupado > 0) {
                $erros[] = 'Este horário já está ocupado, escolha outro';
            }
        }
    }

    if (!empty($erros)) {
        $_SESSION['erros_agendamento'] = $erros;
        redirect('backend/views/painel_cliente.php?acao=agendar');
    }

    // Calcular valores com base no médico selecionado
    $valor_total = (float) $medico['valor_consulta'];
    $valor_medico = calcular_valor_medico($valor_total, $medico['percentual_medico']);

    // Inserir agendamento
    $stmt = $conexao_db->prepare(
        "INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento)
         VALUES (?, 'consulta', ?, ?, ?, 'pendente', ?, ?, ?, 'pendente')"
    );

    if ($stmt) {
        $stmt->bind_param('iiissdd', $id_cliente, $id_especialidade, $id_medico, $data_hora, $notas, $valor_total, $valor_medico);

        if ($stmt->execute()) {
            set_flash_message('agendamento', SUCESSO_AGENDAMENTO, 'sucesso');
            log_acao('AGENDAR_CONSULTA', $id_cliente, "Especialidade: $id_especialidade, Médico: $id_medico, Data: $data_hora");
            redirect('backend/views/painel_cliente.php?acao=agendamentos');
        } else {
            $erros[] = 'Erro ao agendar: ' . $conexao_db->error;
        }
        $stmt->close();
    }

    $_SESSION['erros_agendamento'] = $erros;
    redirect('backend/views/painel_cliente.php?acao=agendar');
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
        redirect('backend/views/painel_cliente.php?acao=exames');
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
            redirect('backend/views/painel_cliente.php?acao=agendamentos');
        } else {
            $erros[] = 'Erro ao solicitar exame: ' . $conexao_db->error;
        }
        $stmt->close();
    }

    $_SESSION['erros_agendamento'] = $erros;
    redirect('backend/views/painel_cliente.php?acao=exames');
}

// ====== REAGENDAR CONSULTA ======
elseif ($acao === 'alterar_agendamento') {
    $id_agendamento   = intval($_POST['id_agendamento'] ?? 0);
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);
    $id_medico        = intval($_POST['id_medico'] ?? 0);
    $data             = sanitizar_input($_POST['data'] ?? '');
    $horario          = sanitizar_input($_POST['horario'] ?? '');
    $notas            = sanitizar_input($_POST['notas'] ?? '');

    // Verificar propriedade e status
    $stmt = $conexao_db->prepare('SELECT * FROM agendamentos WHERE id = ? AND id_cliente = ?');
    $stmt->bind_param('ii', $id_agendamento, $id_cliente);
    $stmt->execute();
    $ag_atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ag_atual || !pode_cancelar_agendamento($ag_atual['status'])) {
        $_SESSION['erros_agendamento'] = ['Agendamento não encontrado ou não pode ser reagendado'];
        redirect('backend/views/painel_cliente.php?acao=agendamentos');
    }

    $erros = [];

    if ($id_especialidade <= 0) $erros[] = 'Especialidade inválida';
    if ($id_medico <= 0)        $erros[] = 'Selecione um médico';

    if (!validar_data($data) || empty($horario) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario)) {
        $erros[] = 'Data e hora inválidas';
    }

    $data_hora = $data . ' ' . $horario . ':00';

    if (empty($erros) && !validar_data_agendamento($data_hora)) {
        $erros[] = 'A data/hora deve ser no futuro (mínimo 1 hora)';
    }

    $medico = null;
    if (empty($erros)) {
        $stmt = $conexao_db->prepare('SELECT * FROM medicos WHERE id = ? AND id_especialidade = ? AND ativo = 1');
        $stmt->bind_param('ii', $id_medico, $id_especialidade);
        $stmt->execute();
        $medico = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$medico) $erros[] = 'Médico inválido para a especialidade selecionada';
    }

    if (empty($erros)) {
        $dia_semana = (int) date('w', strtotime($data));
        $stmt = $conexao_db->prepare(
            'SELECT hora_inicio, hora_fim, intervalo_minutos FROM horarios_atendimento
             WHERE id_medico = ? AND dia_semana = ? AND ativo = 1'
        );
        $stmt->bind_param('ii', $id_medico, $dia_semana);
        $stmt->execute();
        $janelas = $stmt->get_result()->fetch_all();
        $stmt->close();

        $disponivel = false;
        foreach ($janelas as $j) {
            if (in_array($horario, gerar_slots_horario($j['hora_inicio'], $j['hora_fim'], $j['intervalo_minutos']), true)) {
                $disponivel = true;
                break;
            }
        }
        if (!$disponivel) $erros[] = 'O médico não atende neste horário';
    }

    if (empty($erros)) {
        // Verifica disponibilidade excluindo o próprio agendamento
        $stmt = $conexao_db->prepare(
            "SELECT COUNT(*) as total FROM agendamentos
             WHERE id_medico = ? AND status IN ('pendente','confirmado') AND data_hora = ? AND id != ?"
        );
        $stmt->bind_param('isi', $id_medico, $data_hora, $id_agendamento);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['total'] > 0) {
            $erros[] = 'Este horário já está ocupado, escolha outro';
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_agendamento'] = $erros;
        redirect('backend/views/painel_cliente.php?acao=editar_agendamento&id=' . $id_agendamento);
    }

    $valor_total  = (float) $medico['valor_consulta'];
    $valor_medico = calcular_valor_medico($valor_total, $medico['percentual_medico']);

    $stmt = $conexao_db->prepare(
        'UPDATE agendamentos
         SET id_especialidade=?, id_medico=?, data_hora=?, notas=?, valor_total=?, valor_medico=?
         WHERE id=? AND id_cliente=?'
    );
    $stmt->bind_param('iissddii', $id_especialidade, $id_medico, $data_hora, $notas, $valor_total, $valor_medico, $id_agendamento, $id_cliente);

    if ($stmt->execute()) {
        set_flash_message('agendamento', 'Consulta reagendada com sucesso!', 'sucesso');
        log_acao('REAGENDAMENTO', $id_cliente, "Agendamento: $id_agendamento -> $data_hora");
    } else {
        $_SESSION['erros_agendamento'] = ['Erro ao reagendar. Tente novamente.'];
    }
    $stmt->close();

    redirect('backend/views/painel_cliente.php?acao=agendamentos');
}

// ====== CANCELAR AGENDAMENTO ======
elseif ($acao === 'cancelar_agendamento') {
    $id_agendamento = intval($_POST['id_agendamento'] ?? 0);

    // Verificar se agendamento pertence ao cliente e pode ser cancelado
    $stmt = $conexao_db->prepare('SELECT status, data_hora FROM agendamentos WHERE id = ? AND id_cliente = ?');
    $stmt->bind_param('ii', $id_agendamento, $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows === 0) {
        $_SESSION['erros_agendamento'] = ['Agendamento não encontrado'];
        redirect('backend/views/painel_cliente.php?acao=agendamentos');
    }

    $agendamento = $resultado->fetch_assoc();

    if (!pode_cancelar_agendamento($agendamento['status'])) {
        $_SESSION['erros_agendamento'] = ['Este agendamento não pode ser cancelado'];
        redirect('backend/views/painel_cliente.php?acao=agendamentos');
    }

    // Prazo mínimo: 2 horas de antecedência
    $limite = new DateTime($agendamento['data_hora']);
    $agora  = new DateTime();
    $agora->modify('+2 hours');
    if ($limite <= $agora) {
        $_SESSION['erros_agendamento'] = ['Cancelamentos devem ser feitos com pelo menos 2 horas de antecedência'];
        redirect('backend/views/painel_cliente.php?acao=agendamentos');
    }

    $stmt = $conexao_db->prepare('UPDATE agendamentos SET status = "cancelado" WHERE id = ?');
    $stmt->bind_param('i', $id_agendamento);

    if ($stmt->execute()) {
        set_flash_message('agendamento', SUCESSO_CANCELAMENTO, 'sucesso');
        log_acao('CANCELAR_AGENDAMENTO', $id_cliente, "Agendamento: $id_agendamento");
    }
    $stmt->close();

    redirect('backend/views/painel_cliente.php?acao=agendamentos');
}

// Ação desconhecida
$_SESSION['erros_agendamento'] = ['Ação não permitida'];
redirect('backend/views/painel_cliente.php');

?>
