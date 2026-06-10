<?php
// ====================================================
// ARQUIVO: backend/controllers/financeiro_controller.php
// Descrição: Processa ações financeiras administrativas:
//            marcar atendimentos como realizados e gerar
//            repasses (pagamentos) aos médicos
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Apenas administradores
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$conexao_db = Conexao::getInstance()->getConexao();
$acao = sanitizar_input($_POST['acao'] ?? '');

// Verificação CSRF para todas as ações
$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $_SESSION['erros_financeiro'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_admin.php?acao=agendamentos');
}

// ====== MARCAR AGENDAMENTO COMO REALIZADO ======
if ($acao === 'marcar_realizado') {
    $id_agendamento = intval($_POST['id_agendamento'] ?? 0);

    $stmt = $conexao_db->prepare(
        'SELECT a.*, m.valor_consulta, m.percentual_medico, m.percentual_exame, e.preco as preco_exame
         FROM agendamentos a
         LEFT JOIN medicos m ON a.id_medico = m.id
         LEFT JOIN exames e ON a.id_exame = e.id
         WHERE a.id = ?'
    );
    $stmt->bind_param('i', $id_agendamento);
    $stmt->execute();
    $agendamento = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$agendamento) {
        set_flash_message('financeiro', 'Agendamento não encontrado', 'erro');
        redirect('backend/views/painel_admin.php?acao=agendamentos');
    }

    if (!in_array($agendamento['status'], ['pendente', 'confirmado'])) {
        set_flash_message('financeiro', 'Apenas agendamentos pendentes ou confirmados podem ser marcados como realizados', 'erro');
        redirect('backend/views/painel_admin.php?acao=agendamentos');
    }

    // Recalcular valores no momento da realização, com base nos
    // valores/percentuais atuais cadastrados para o médico
    $valor_total = null;
    $valor_medico = null;

    if ($agendamento['tipo'] === 'consulta' && $agendamento['id_medico']) {
        $valor_total = (float) $agendamento['valor_consulta'];
        $valor_medico = calcular_valor_medico($valor_total, $agendamento['percentual_medico']);
    } elseif ($agendamento['tipo'] === 'exame') {
        $valor_total = (float) $agendamento['preco_exame'];
        if ($agendamento['id_medico']) {
            $valor_medico = calcular_valor_medico($valor_total, $agendamento['percentual_exame']);
        } else {
            $valor_medico = 0;
        }
    }

    $data_realizacao = substr($agendamento['data_hora'], 0, 10);

    $stmt = $conexao_db->prepare(
        "UPDATE agendamentos
         SET status = 'concluído', data_realizacao = ?, valor_total = ?, valor_medico = ?
         WHERE id = ?"
    );
    $stmt->bind_param('sddi', $data_realizacao, $valor_total, $valor_medico, $id_agendamento);
    $stmt->execute();
    $stmt->close();

    set_flash_message('financeiro', 'Atendimento marcado como realizado', 'sucesso');
    log_acao('MARCAR_REALIZADO', $_SESSION['id_cliente'], "Agendamento: $id_agendamento");
    redirect('backend/views/painel_admin.php?acao=agendamentos');
}

// ====== GERAR PAGAMENTO PARA MÉDICO ======
elseif ($acao === 'gerar_pagamento') {
    $id_medico = intval($_POST['id_medico'] ?? 0);
    $periodo_inicio = sanitizar_input($_POST['periodo_inicio'] ?? '');
    $periodo_fim = sanitizar_input($_POST['periodo_fim'] ?? '');

    $erros = [];

    if ($id_medico <= 0) {
        $erros[] = 'Selecione um médico';
    }
    if (!validar_data($periodo_inicio) || !validar_data($periodo_fim)) {
        $erros[] = 'Informe um período válido';
    } elseif ($periodo_inicio > $periodo_fim) {
        $erros[] = 'A data inicial deve ser anterior à data final';
    }

    if (!empty($erros)) {
        $_SESSION['erros_financeiro'] = $erros;
        redirect('backend/views/painel_admin.php?acao=financeiro');
    }

    // Calcular total de atendimentos realizados e não repassados no período
    $stmt = $conexao_db->prepare(
        "SELECT COUNT(*) as total_consultas,
                COALESCE(SUM(valor_total), 0) as valor_total_recebido,
                COALESCE(SUM(valor_medico), 0) as valor_repassado
         FROM agendamentos
         WHERE id_medico = ?
           AND status = 'concluído'
           AND status_pagamento IN ('pendente', 'pago_clinica')
           AND data_realizacao BETWEEN ? AND ?"
    );
    $stmt->bind_param('iss', $id_medico, $periodo_inicio, $periodo_fim);
    $stmt->execute();
    $resumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $resumo['total_consultas'] === 0) {
        $_SESSION['erros_financeiro'] = ['Nenhum atendimento pendente de repasse encontrado para este médico no período informado'];
        redirect('backend/views/painel_admin.php?acao=financeiro');
    }

    $hoje = date('Y-m-d');

    // Criar registro de pagamento
    $stmt = $conexao_db->prepare(
        "INSERT INTO pagamentos_medicos
         (id_medico, periodo_inicio, periodo_fim, total_consultas, valor_total_recebido, valor_repassado, data_pagamento, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pago')"
    );
    $total_consultas = (int) $resumo['total_consultas'];
    $valor_total_recebido = (float) $resumo['valor_total_recebido'];
    $valor_repassado = (float) $resumo['valor_repassado'];
    $stmt->bind_param(
        'issidds',
        $id_medico, $periodo_inicio, $periodo_fim, $total_consultas, $valor_total_recebido, $valor_repassado, $hoje
    );
    $stmt->execute();
    $stmt->close();

    // Atualizar status de pagamento dos agendamentos contemplados
    $stmt = $conexao_db->prepare(
        "UPDATE agendamentos
         SET status_pagamento = 'pago_medico'
         WHERE id_medico = ?
           AND status = 'concluído'
           AND status_pagamento IN ('pendente', 'pago_clinica')
           AND data_realizacao BETWEEN ? AND ?"
    );
    $stmt->bind_param('iss', $id_medico, $periodo_inicio, $periodo_fim);
    $stmt->execute();
    $stmt->close();

    set_flash_message('financeiro', "Pagamento gerado com sucesso: $total_consultas atendimento(s), " . formatar_valor($valor_repassado) . ' repassados', 'sucesso');
    log_acao('GERAR_PAGAMENTO', $_SESSION['id_cliente'], "Médico: $id_medico, Período: $periodo_inicio a $periodo_fim");
    redirect('backend/views/painel_admin.php?acao=financeiro');
}

// Ação desconhecida
$_SESSION['erros_financeiro'] = ['Ação não permitida'];
redirect('backend/views/painel_admin.php?acao=agendamentos');

?>
