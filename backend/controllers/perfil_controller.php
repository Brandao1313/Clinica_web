<?php
// ====================================================
// ARQUIVO: backend/controllers/perfil_controller.php
// Descrição: Atualiza dados de perfil e senha do cliente
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$acao       = sanitizar_input($_POST['acao'] ?? '');
$id_cliente = $_SESSION['id_cliente'];
$db         = Conexao::getInstance()->getConexao();

if (!validar_token_csrf($_POST['csrf_token'] ?? '')) {
    set_flash_message('perfil', 'Token de segurança inválido. Tente novamente.', 'erro');
    redirect('backend/views/painel_cliente.php?acao=perfil');
}

// ====== ATUALIZAR DADOS DO PERFIL ======
if ($acao === 'atualizar_perfil') {
    $nome     = sanitizar_input($_POST['nome'] ?? '');
    $email    = sanitizar_input($_POST['email'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    $erros = [];

    if (!validar_nome($nome)) {
        $erros[] = 'Nome inválido (mínimo 3 caracteres, apenas letras e espaços)';
    }
    if (!validar_email($email)) {
        $erros[] = 'E-mail inválido';
    }
    if (!validar_telefone($telefone)) {
        $erros[] = 'Telefone inválido (10 ou 11 dígitos)';
    }

    // Unicidade de e-mail (excluindo o próprio usuário)
    if (empty($erros)) {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $email, $id_cliente);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = ERRO_EMAIL_EXISTE;
        }
        $stmt->close();
    }

    // Unicidade de telefone (excluindo o próprio usuário)
    if (empty($erros)) {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE telefone = ? AND id != ?');
        $stmt->bind_param('si', $telefone, $id_cliente);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = ERRO_TELEFONE_EXISTE;
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_perfil'] = $erros;
        redirect('backend/views/painel_cliente.php?acao=editar_perfil');
    }

    $stmt = $db->prepare('UPDATE clientes SET nome = ?, email = ?, telefone = ? WHERE id = ?');
    $stmt->bind_param('sssi', $nome, $email, $telefone, $id_cliente);

    if ($stmt->execute()) {
        $_SESSION['nome_cliente']  = $nome;
        $_SESSION['email_cliente'] = $email;
        log_acao('PERFIL_ATUALIZADO', $id_cliente, "Nome: {$nome}, Email: {$email}");
        set_flash_message('perfil', 'Perfil atualizado com sucesso!', 'sucesso');
    } else {
        set_flash_message('perfil', 'Erro ao atualizar perfil. Tente novamente.', 'erro');
    }
    $stmt->close();

    redirect('backend/views/painel_cliente.php?acao=perfil');
}

// ====== ALTERAR SENHA ======
elseif ($acao === 'alterar_senha') {
    $senha_atual      = $_POST['senha_atual']      ?? '';
    $nova_senha       = $_POST['nova_senha']       ?? '';
    $confirmar_senha  = $_POST['confirmar_senha']  ?? '';

    $erros = [];

    $stmt = $db->prepare('SELECT senha_hash FROM clientes WHERE id = ?');
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !verificar_senha($senha_atual, $row['senha_hash'])) {
        $erros[] = 'Senha atual incorreta';
    }
    if (strlen($nova_senha) < PASSWORD_MIN_LENGTH) {
        $erros[] = ERRO_SENHA_FRACA;
    }
    if ($nova_senha !== $confirmar_senha) {
        $erros[] = ERRO_SENHAS_NAOCOMPAT;
    }

    if (!empty($erros)) {
        $_SESSION['erros_perfil'] = $erros;
        redirect('backend/views/painel_cliente.php?acao=alterar_senha');
    }

    $novo_hash = gerar_hash_senha($nova_senha);
    $stmt = $db->prepare('UPDATE clientes SET senha_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $novo_hash, $id_cliente);

    if ($stmt->execute()) {
        log_acao('SENHA_ALTERADA', $id_cliente, 'Senha alterada pelo próprio cliente');
        set_flash_message('perfil', 'Senha alterada com sucesso!', 'sucesso');
    } else {
        set_flash_message('perfil', 'Erro ao alterar senha. Tente novamente.', 'erro');
    }
    $stmt->close();

    redirect('backend/views/painel_cliente.php?acao=perfil');
}

set_flash_message('perfil', 'Ação não permitida.', 'erro');
redirect('backend/views/painel_cliente.php?acao=perfil');
?>
