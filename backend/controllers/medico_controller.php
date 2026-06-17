<?php
// ====================================================
// ARQUIVO: backend/controllers/medico_controller.php
// Descrição: Processa ações administrativas de médicos
//            (CRUD) e de horários de atendimento
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
    $_SESSION['erros_medico'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_admin.php?acao=medicos');
}

// ====== SALVAR MÉDICO (criar ou editar) ======
if ($acao === 'salvar_medico') {
    $id_medico = intval($_POST['id_medico'] ?? 0);
    $nome = sanitizar_input($_POST['nome'] ?? '');
    $crm = sanitizar_input($_POST['crm'] ?? '');
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);
    $email = sanitizar_input($_POST['email'] ?? '');
    $telefone = sanitizar_input($_POST['telefone'] ?? '');
    $valor_consulta = (float) str_replace(',', '.', $_POST['valor_consulta'] ?? '0');
    $percentual_medico = (float) str_replace(',', '.', $_POST['percentual_medico'] ?? '0');
    $percentual_exame = (float) str_replace(',', '.', $_POST['percentual_exame'] ?? '0');
    $bio = sanitizar_input($_POST['bio'] ?? '');
    $foto = sanitizar_input($_POST['foto'] ?? '');
    $ativo           = isset($_POST['ativo']) ? 1 : 0;
    $senha           = $_POST['senha'] ?? '';
    $confirmacao_senha = $_POST['confirmacao_senha'] ?? '';

    $erros = [];

    if (strlen($nome) < 3) {
        $erros[] = 'Informe o nome completo do médico';
    }
    if (empty($crm)) {
        $erros[] = 'Informe o CRM do médico';
    }
    if ($id_especialidade <= 0) {
        $erros[] = 'Selecione uma especialidade';
    }
    if (!empty($email) && !validar_email($email)) {
        $erros[] = ERRO_EMAIL_INVALIDO;
    }
    if ($valor_consulta <= 0) {
        $erros[] = 'O valor da consulta deve ser maior que zero';
    }
    if ($percentual_medico < 0 || $percentual_medico > 100) {
        $erros[] = 'O percentual do médico deve estar entre 0 e 100';
    }
    if ($percentual_exame < 0 || $percentual_exame > 100) {
        $erros[] = 'O percentual sobre exames deve estar entre 0 e 100';
    }

    // Senha: obrigatória no cadastro, opcional na edição
    if ($id_medico === 0 && empty($senha)) {
        $erros[] = 'Informe uma senha para o médico';
    }
    if (!empty($senha)) {
        if (!validar_senha_forte($senha)) {
            $erros[] = ERRO_SENHA_FRACA;
        } elseif ($senha !== $confirmacao_senha) {
            $erros[] = ERRO_SENHAS_NAOCOMPAT;
        }
    }

    // Verificar CRM único
    if (empty($erros)) {
        $stmt = $conexao_db->prepare('SELECT id FROM medicos WHERE crm = ? AND id != ?');
        $stmt->bind_param('si', $crm, $id_medico);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe um médico cadastrado com este CRM';
        }
        $stmt->close();
    }

    // Verificar email único (se informado)
    if (empty($erros) && !empty($email)) {
        $stmt = $conexao_db->prepare('SELECT id FROM medicos WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $email, $id_medico);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = ERRO_EMAIL_EXISTE;
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_medico'] = $erros;
        $_SESSION['dados_medico'] = $_POST;
        if ($id_medico > 0) {
            redirect('backend/views/painel_admin.php?acao=medico_form&id=' . $id_medico);
        }
        redirect('backend/views/painel_admin.php?acao=medico_form');
    }

    if ($id_medico > 0) {
        // Atualizar médico existente
        if (!empty($senha)) {
            $hash_senha = gerar_hash_senha($senha);
            $stmt = $conexao_db->prepare(
                'UPDATE medicos SET nome = ?, crm = ?, id_especialidade = ?, email = ?, telefone = ?,
                 valor_consulta = ?, percentual_medico = ?, percentual_exame = ?, bio = ?, foto = ?, ativo = ?, senha_hash = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssisssdddsssi',
                $nome, $crm, $id_especialidade, $email, $telefone,
                $valor_consulta, $percentual_medico, $percentual_exame, $bio, $foto, $ativo, $hash_senha, $id_medico
            );
            $stmt->execute();
            $stmt->close();

            // Sincronizar senha na conta clientes vinculada
            $s2 = $conexao_db->prepare('SELECT id_cliente FROM medicos WHERE id = ?');
            $s2->bind_param('i', $id_medico);
            $s2->execute();
            $r2 = $s2->get_result()->fetch_assoc();
            $s2->close();
            if (!empty($r2['id_cliente'])) {
                $s3 = $conexao_db->prepare('UPDATE clientes SET senha_hash = ?, nome = ?, ativo = ? WHERE id = ?');
                $s3->bind_param('ssii', $hash_senha, $nome, $ativo, $r2['id_cliente']);
                $s3->execute();
                $s3->close();
            }
        } else {
            $stmt = $conexao_db->prepare(
                'UPDATE medicos SET nome = ?, crm = ?, id_especialidade = ?, email = ?, telefone = ?,
                 valor_consulta = ?, percentual_medico = ?, percentual_exame = ?, bio = ?, foto = ?, ativo = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssisssdddssi',
                $nome, $crm, $id_especialidade, $email, $telefone,
                $valor_consulta, $percentual_medico, $percentual_exame, $bio, $foto, $ativo, $id_medico
            );
            $stmt->execute();
            $stmt->close();

            // Sincronizar nome/ativo na conta clientes vinculada
            $s2 = $conexao_db->prepare('SELECT id_cliente FROM medicos WHERE id = ?');
            $s2->bind_param('i', $id_medico);
            $s2->execute();
            $r2 = $s2->get_result()->fetch_assoc();
            $s2->close();
            if (!empty($r2['id_cliente'])) {
                $s3 = $conexao_db->prepare('UPDATE clientes SET nome = ?, ativo = ? WHERE id = ?');
                $s3->bind_param('sii', $nome, $ativo, $r2['id_cliente']);
                $s3->execute();
                $s3->close();
            }
        }
        set_flash_message('medico', 'Médico atualizado com sucesso', 'sucesso');
        log_acao('ATUALIZAR_MEDICO', $_SESSION['id_cliente'], "Médico: $id_medico");
    } else {
        // Criar novo médico — também cria conta de login em clientes
        $hash_senha = gerar_hash_senha($senha);

        // 1. Criar conta de login
        $telefone_login = !empty($telefone) ? $telefone : '99' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $stmt_c = $conexao_db->prepare(
            'INSERT INTO clientes (nome, email, senha_hash, telefone, cpf, tipo, ativo)
             VALUES (?, ?, ?, ?, ?, "medico", ?)'
        );
        $cpf_fake = '000.000.000-00';
        $stmt_c->bind_param('sssssi', $nome, $email, $hash_senha, $telefone_login, $cpf_fake, $ativo);
        $stmt_c->execute();
        $id_cliente_novo = $conexao_db->insert_id;
        $stmt_c->close();

        // 2. Criar registro clínico
        $stmt = $conexao_db->prepare(
            'INSERT INTO medicos (nome, crm, id_especialidade, email, telefone, valor_consulta, percentual_medico,
             percentual_exame, bio, foto, ativo, senha_hash, id_cliente)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssisssdddsisi',
            $nome, $crm, $id_especialidade, $email, $telefone,
            $valor_consulta, $percentual_medico, $percentual_exame, $bio, $foto, $ativo, $hash_senha, $id_cliente_novo
        );
        $stmt->execute();
        $stmt->close();

        set_flash_message('medico', 'Médico cadastrado com sucesso. Login: ' . htmlspecialchars($email), 'sucesso');
        log_acao('CRIAR_MEDICO', $_SESSION['id_cliente'], "CRM: $crm email: $email");
    }

    unset($_SESSION['dados_medico']);
    redirect('backend/views/painel_admin.php?acao=medicos');
}

// ====== ALTERNAR STATUS (ativar/desativar) ======
elseif ($acao === 'alternar_status_medico') {
    $id_medico = intval($_POST['id_medico'] ?? 0);

    $stmt = $conexao_db->prepare('SELECT ativo FROM medicos WHERE id = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $medico = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($medico) {
        $novo_status = $medico['ativo'] ? 0 : 1;
        $stmt = $conexao_db->prepare('UPDATE medicos SET ativo = ? WHERE id = ?');
        $stmt->bind_param('ii', $novo_status, $id_medico);
        $stmt->execute();
        $stmt->close();
        set_flash_message('medico', $novo_status ? 'Médico ativado com sucesso' : 'Médico desativado com sucesso', 'sucesso');
        log_acao('ALTERNAR_STATUS_MEDICO', $_SESSION['id_cliente'], "Médico: $id_medico -> $novo_status");
    }

    redirect('backend/views/painel_admin.php?acao=medicos');
}

// ====== EXCLUIR MÉDICO ======
elseif ($acao === 'excluir_medico') {
    $id_medico = intval($_POST['id_medico'] ?? 0);

    // Verificar se há agendamentos futuros ou pendentes/confirmados para este médico
    $stmt = $conexao_db->prepare(
        "SELECT COUNT(*) as total FROM agendamentos
         WHERE id_medico = ? AND status IN ('pendente', 'confirmado')
         AND data_hora >= datetime('now', 'localtime')"
    );
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total > 0) {
        set_flash_message('medico', 'Não é possível excluir: o médico possui agendamentos futuros ou pendentes', 'erro');
        redirect('backend/views/painel_admin.php?acao=medicos');
    }

    $stmt = $conexao_db->prepare('DELETE FROM medicos WHERE id = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $stmt->close();

    set_flash_message('medico', 'Médico excluído com sucesso', 'sucesso');
    log_acao('EXCLUIR_MEDICO', $_SESSION['id_cliente'], "Médico: $id_medico");
    redirect('backend/views/painel_admin.php?acao=medicos');
}

// ====== SALVAR HORÁRIOS DE ATENDIMENTO ======
elseif ($acao === 'salvar_horarios') {
    $id_medico = intval($_POST['id_medico'] ?? 0);

    $stmt = $conexao_db->prepare('SELECT id FROM medicos WHERE id = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        set_flash_message('medico', 'Médico não encontrado', 'erro');
        redirect('backend/views/painel_admin.php?acao=medicos');
    }
    $stmt->close();

    // Estrutura esperada: dia[] = dia da semana marcado,
    // hora_inicio[dia], hora_fim[dia], intervalo[dia]
    $dias_marcados = $_POST['dia'] ?? [];
    $horas_inicio = $_POST['hora_inicio'] ?? [];
    $horas_fim = $_POST['hora_fim'] ?? [];
    $intervalos = $_POST['intervalo'] ?? [];

    $novos_horarios = [];
    $erros = [];

    foreach ($dias_marcados as $dia) {
        $dia = intval($dia);
        if ($dia < 0 || $dia > 6) {
            continue;
        }

        $inicio = sanitizar_input($horas_inicio[$dia] ?? '');
        $fim = sanitizar_input($horas_fim[$dia] ?? '');
        $intervalo = intval($intervalos[$dia] ?? 30);

        if (empty($inicio) || empty($fim)) {
            $erros[] = obter_nome_dia_semana($dia) . ': informe o horário de início e fim';
            continue;
        }

        if ($inicio >= $fim) {
            $erros[] = obter_nome_dia_semana($dia) . ': o horário de início deve ser antes do horário de fim';
            continue;
        }

        if ($intervalo < 5 || $intervalo > 240) {
            $erros[] = obter_nome_dia_semana($dia) . ': intervalo de consulta inválido';
            continue;
        }

        // Verificar sobreposição entre os próprios horários enviados
        foreach ($novos_horarios as $existente) {
            if ($existente['dia'] === $dia && $inicio < $existente['fim'] && $fim > $existente['inicio']) {
                $erros[] = obter_nome_dia_semana($dia) . ': horários sobrepostos';
                continue 2;
            }
        }

        $novos_horarios[] = [
            'dia' => $dia,
            'inicio' => $inicio,
            'fim' => $fim,
            'intervalo' => $intervalo,
        ];
    }

    if (!empty($erros)) {
        $_SESSION['erros_horarios'] = $erros;
        redirect('backend/views/painel_admin.php?acao=horarios&id=' . $id_medico);
    }

    // Substituir todos os horários do médico pelos novos
    $stmt = $conexao_db->prepare('DELETE FROM horarios_atendimento WHERE id_medico = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $stmt->close();

    foreach ($novos_horarios as $horario) {
        $stmt = $conexao_db->prepare(
            'INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->bind_param('iissi', $id_medico, $horario['dia'], $horario['inicio'], $horario['fim'], $horario['intervalo']);
        $stmt->execute();
        $stmt->close();
    }

    set_flash_message('medico', 'Horários de atendimento atualizados com sucesso', 'sucesso');
    log_acao('SALVAR_HORARIOS', $_SESSION['id_cliente'], "Médico: $id_medico");
    redirect('backend/views/painel_admin.php?acao=horarios&id=' . $id_medico);
}

// Ação desconhecida
$_SESSION['erros_medico'] = ['Ação não permitida'];
redirect('backend/views/painel_admin.php?acao=medicos');

?>
