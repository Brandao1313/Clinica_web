<?php
// ====================================================
// ARQUIVO: backend/controllers/especialidade_controller.php
// Descrição: Processa ações administrativas de
//            especialidades (CRUD)
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
    $_SESSION['erros_especialidade'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_admin.php?acao=especialidades');
}

// ====== SALVAR ESPECIALIDADE (criar ou editar) ======
if ($acao === 'salvar_especialidade') {
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);
    $nome = sanitizar_input($_POST['nome'] ?? '');
    $descricao = sanitizar_input($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $erros = [];

    if (strlen($nome) < 3) {
        $erros[] = 'Informe o nome da especialidade (mínimo 3 caracteres)';
    }

    // Verificar nome único
    if (empty($erros)) {
        $stmt = $conexao_db->prepare('SELECT id FROM especialidades WHERE nome = ? AND id != ?');
        $stmt->bind_param('si', $nome, $id_especialidade);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe uma especialidade cadastrada com este nome';
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_especialidade'] = $erros;
        $_SESSION['dados_especialidade'] = $_POST;
        if ($id_especialidade > 0) {
            redirect('backend/views/painel_admin.php?acao=especialidade_form&id=' . $id_especialidade);
        }
        redirect('backend/views/painel_admin.php?acao=especialidade_form');
    }

    if ($id_especialidade > 0) {
        $stmt = $conexao_db->prepare('UPDATE especialidades SET nome = ?, descricao = ?, ativo = ? WHERE id = ?');
        $stmt->bind_param('ssii', $nome, $descricao, $ativo, $id_especialidade);
        $stmt->execute();
        $stmt->close();
        set_flash_message('especialidade', 'Especialidade atualizada com sucesso', 'sucesso');
        log_acao('ATUALIZAR_ESPECIALIDADE', $_SESSION['id_cliente'], "Especialidade: $id_especialidade");
    } else {
        $stmt = $conexao_db->prepare('INSERT INTO especialidades (nome, descricao, ativo) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $nome, $descricao, $ativo);
        $stmt->execute();
        $stmt->close();
        set_flash_message('especialidade', 'Especialidade cadastrada com sucesso', 'sucesso');
        log_acao('CRIAR_ESPECIALIDADE', $_SESSION['id_cliente'], "Nome: $nome");
    }

    unset($_SESSION['dados_especialidade']);
    redirect('backend/views/painel_admin.php?acao=especialidades');
}

// ====== ALTERNAR STATUS (ativar/desativar) ======
elseif ($acao === 'alternar_status_especialidade') {
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);

    $stmt = $conexao_db->prepare('SELECT ativo FROM especialidades WHERE id = ?');
    $stmt->bind_param('i', $id_especialidade);
    $stmt->execute();
    $especialidade = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($especialidade) {
        $novo_status = $especialidade['ativo'] ? 0 : 1;
        $stmt = $conexao_db->prepare('UPDATE especialidades SET ativo = ? WHERE id = ?');
        $stmt->bind_param('ii', $novo_status, $id_especialidade);
        $stmt->execute();
        $stmt->close();
        set_flash_message('especialidade', $novo_status ? 'Especialidade ativada com sucesso' : 'Especialidade desativada com sucesso', 'sucesso');
        log_acao('ALTERNAR_STATUS_ESPECIALIDADE', $_SESSION['id_cliente'], "Especialidade: $id_especialidade -> $novo_status");
    }

    redirect('backend/views/painel_admin.php?acao=especialidades');
}

// ====== EXCLUIR ESPECIALIDADE ======
elseif ($acao === 'excluir_especialidade') {
    $id_especialidade = intval($_POST['id_especialidade'] ?? 0);

    // Verificar se há médicos vinculados
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM medicos WHERE id_especialidade = ?');
    $stmt->bind_param('i', $id_especialidade);
    $stmt->execute();
    $total_medicos = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total_medicos > 0) {
        set_flash_message('especialidade', 'Não é possível excluir: existem médicos vinculados a esta especialidade', 'erro');
        redirect('backend/views/painel_admin.php?acao=especialidades');
    }

    // Verificar se há agendamentos vinculados
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM agendamentos WHERE id_especialidade = ?');
    $stmt->bind_param('i', $id_especialidade);
    $stmt->execute();
    $total_agendamentos = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total_agendamentos > 0) {
        set_flash_message('especialidade', 'Não é possível excluir: existem agendamentos vinculados a esta especialidade', 'erro');
        redirect('backend/views/painel_admin.php?acao=especialidades');
    }

    $stmt = $conexao_db->prepare('DELETE FROM especialidades WHERE id = ?');
    $stmt->bind_param('i', $id_especialidade);
    $stmt->execute();
    $stmt->close();

    set_flash_message('especialidade', 'Especialidade excluída com sucesso', 'sucesso');
    log_acao('EXCLUIR_ESPECIALIDADE', $_SESSION['id_cliente'], "Especialidade: $id_especialidade");
    redirect('backend/views/painel_admin.php?acao=especialidades');
}

// Ação desconhecida
$_SESSION['erros_especialidade'] = ['Ação não permitida'];
redirect('backend/views/painel_admin.php?acao=especialidades');

?>
