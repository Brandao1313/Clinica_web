<?php
// ====================================================
// ARQUIVO: backend/controllers/exame_controller.php
// Descrição: Processa ações administrativas de
//            exames (CRUD)
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
    $_SESSION['erros_exame'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_admin.php?acao=exames');
}

// ====== SALVAR EXAME (criar ou editar) ======
if ($acao === 'salvar_exame') {
    $id_exame = intval($_POST['id_exame'] ?? 0);
    $nome = sanitizar_input($_POST['nome'] ?? '');
    $descricao = sanitizar_input($_POST['descricao'] ?? '');
    $preco = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $erros = [];

    if (strlen($nome) < 3) {
        $erros[] = 'Informe o nome do exame (mínimo 3 caracteres)';
    }
    if ($preco <= 0) {
        $erros[] = 'O preço do exame deve ser maior que zero';
    }

    // Verificar nome único
    if (empty($erros)) {
        $stmt = $conexao_db->prepare('SELECT id FROM exames WHERE nome = ? AND id != ?');
        $stmt->bind_param('si', $nome, $id_exame);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe um exame cadastrado com este nome';
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_exame'] = $erros;
        $_SESSION['dados_exame'] = $_POST;
        if ($id_exame > 0) {
            redirect('backend/views/painel_admin.php?acao=exame_form&id=' . $id_exame);
        }
        redirect('backend/views/painel_admin.php?acao=exame_form');
    }

    if ($id_exame > 0) {
        $stmt = $conexao_db->prepare('UPDATE exames SET nome = ?, descricao = ?, preco = ?, ativo = ? WHERE id = ?');
        $stmt->bind_param('ssdii', $nome, $descricao, $preco, $ativo, $id_exame);
        $stmt->execute();
        $stmt->close();
        set_flash_message('exame', 'Exame atualizado com sucesso', 'sucesso');
        log_acao('ATUALIZAR_EXAME', $_SESSION['id_cliente'], "Exame: $id_exame");
    } else {
        $stmt = $conexao_db->prepare('INSERT INTO exames (nome, descricao, preco, ativo) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssdi', $nome, $descricao, $preco, $ativo);
        $stmt->execute();
        $stmt->close();
        set_flash_message('exame', 'Exame cadastrado com sucesso', 'sucesso');
        log_acao('CRIAR_EXAME', $_SESSION['id_cliente'], "Nome: $nome");
    }

    unset($_SESSION['dados_exame']);
    redirect('backend/views/painel_admin.php?acao=exames');
}

// ====== ALTERNAR STATUS (ativar/desativar) ======
elseif ($acao === 'alternar_status_exame') {
    $id_exame = intval($_POST['id_exame'] ?? 0);

    $stmt = $conexao_db->prepare('SELECT ativo FROM exames WHERE id = ?');
    $stmt->bind_param('i', $id_exame);
    $stmt->execute();
    $exame = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exame) {
        $novo_status = $exame['ativo'] ? 0 : 1;
        $stmt = $conexao_db->prepare('UPDATE exames SET ativo = ? WHERE id = ?');
        $stmt->bind_param('ii', $novo_status, $id_exame);
        $stmt->execute();
        $stmt->close();
        set_flash_message('exame', $novo_status ? 'Exame ativado com sucesso' : 'Exame desativado com sucesso', 'sucesso');
        log_acao('ALTERNAR_STATUS_EXAME', $_SESSION['id_cliente'], "Exame: $id_exame -> $novo_status");
    }

    redirect('backend/views/painel_admin.php?acao=exames');
}

// ====== EXCLUIR EXAME ======
elseif ($acao === 'excluir_exame') {
    $id_exame = intval($_POST['id_exame'] ?? 0);

    // Verificar se há agendamentos vinculados
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM agendamentos WHERE id_exame = ?');
    $stmt->bind_param('i', $id_exame);
    $stmt->execute();
    $total_agendamentos = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total_agendamentos > 0) {
        set_flash_message('exame', 'Não é possível excluir: existem agendamentos vinculados a este exame', 'erro');
        redirect('backend/views/painel_admin.php?acao=exames');
    }

    $stmt = $conexao_db->prepare('DELETE FROM exames WHERE id = ?');
    $stmt->bind_param('i', $id_exame);
    $stmt->execute();
    $stmt->close();

    set_flash_message('exame', 'Exame excluído com sucesso', 'sucesso');
    log_acao('EXCLUIR_EXAME', $_SESSION['id_cliente'], "Exame: $id_exame");
    redirect('backend/views/painel_admin.php?acao=exames');
}

// Ação desconhecida
$_SESSION['erros_exame'] = ['Ação não permitida'];
redirect('backend/views/painel_admin.php?acao=exames');

?>
