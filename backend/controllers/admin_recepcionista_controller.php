<?php
// ====================================================
// ARQUIVO: backend/controllers/admin_recepcionista_controller.php
// Descrição: CRUD de recepcionistas (painel admin)
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$db   = Conexao::getInstance()->getConexao();
$acao = sanitizar_input($_POST['acao'] ?? '');

if (!validar_token_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['erros_recepcionista'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_admin.php?acao=recepcionistas');
}

// ====== SALVAR (criar ou editar) ======
if ($acao === 'salvar_recepcionista') {
    $id          = intval($_POST['id_recepcionista'] ?? 0);
    $nome        = sanitizar_input($_POST['nome'] ?? '');
    $email       = sanitizar_input($_POST['email'] ?? '');
    $cpf         = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone    = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $ativo       = isset($_POST['ativo']) ? 1 : 0;
    $senha       = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao_senha'] ?? '';

    $erros = [];

    if (!validar_nome($nome)) {
        $erros[] = 'Nome inválido (mínimo 3 caracteres, apenas letras)';
    }
    if (!validar_email($email)) {
        $erros[] = ERRO_EMAIL_INVALIDO;
    }
    if (!validar_cpf($cpf)) {
        $erros[] = ERRO_CPF_INVALIDO;
    }
    if (!empty($telefone) && !validar_telefone($telefone)) {
        $erros[] = ERRO_TELEFONE_INVALIDO;
    }
    if ($id === 0 && empty($senha)) {
        $erros[] = 'Informe uma senha para a recepcionista';
    }
    if (!empty($senha)) {
        if (!validar_senha_forte($senha)) {
            $erros[] = ERRO_SENHA_FRACA;
        } elseif ($senha !== $confirmacao) {
            $erros[] = ERRO_SENHAS_NAOCOMPAT;
        }
    }

    // Unicidade de e-mail
    if (empty($erros)) {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = ERRO_EMAIL_EXISTE;
        }
        $stmt->close();
    }

    // Unicidade de CPF
    if (empty($erros)) {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE cpf = ? AND id != ?');
        $stmt->bind_param('si', $cpf, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = ERRO_CPF_EXISTE;
        }
        $stmt->close();
    }

    if (!empty($erros)) {
        $_SESSION['erros_recepcionista'] = $erros;
        $_SESSION['dados_recepcionista'] = $_POST;
        redirect('backend/views/painel_admin.php?acao=recepcionista_form' . ($id > 0 ? '&id=' . $id : ''));
    }

    if ($id > 0) {
        if (!empty($senha)) {
            $hash = gerar_hash_senha($senha);
            $stmt = $db->prepare(
                'UPDATE clientes SET nome=?, email=?, cpf=?, telefone=?, ativo=?, senha_hash=?
                 WHERE id=? AND tipo="recepcionista"'
            );
            $stmt->bind_param('ssssiisi', $nome, $email, $cpf, $telefone, $ativo, $hash, $id);
        } else {
            $stmt = $db->prepare(
                'UPDATE clientes SET nome=?, email=?, cpf=?, telefone=?, ativo=?
                 WHERE id=? AND tipo="recepcionista"'
            );
            $stmt->bind_param('ssssii', $nome, $email, $cpf, $telefone, $ativo, $id);
        }
        $stmt->execute();
        $stmt->close();
        set_flash_message('recepcionista', 'Recepcionista atualizada com sucesso', 'sucesso');
        log_acao('ATUALIZAR_RECEPCIONISTA', $_SESSION['id_cliente'], "ID: $id");
    } else {
        $hash = gerar_hash_senha($senha);
        $stmt = $db->prepare(
            'INSERT INTO clientes (nome, email, cpf, telefone, senha_hash, tipo, ativo, eh_recepcionista)
             VALUES (?, ?, ?, ?, ?, "recepcionista", ?, 1)'
        );
        $stmt->bind_param('sssssi', $nome, $email, $cpf, $telefone, $hash, $ativo);
        $stmt->execute();
        $stmt->close();
        set_flash_message('recepcionista', 'Recepcionista cadastrada. Login: ' . htmlspecialchars($email), 'sucesso');
        log_acao('CRIAR_RECEPCIONISTA', $_SESSION['id_cliente'], "Email: $email");
    }

    unset($_SESSION['dados_recepcionista']);
    redirect('backend/views/painel_admin.php?acao=recepcionistas');
}

// ====== ALTERNAR STATUS ======
elseif ($acao === 'alternar_status_recepcionista') {
    $id = intval($_POST['id_recepcionista'] ?? 0);

    $stmt = $db->prepare('SELECT ativo FROM clientes WHERE id=? AND tipo="recepcionista"');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $novo = $row['ativo'] ? 0 : 1;
        $stmt = $db->prepare('UPDATE clientes SET ativo=? WHERE id=?');
        $stmt->bind_param('ii', $novo, $id);
        $stmt->execute();
        $stmt->close();
        set_flash_message('recepcionista', $novo ? 'Recepcionista ativada' : 'Recepcionista desativada', 'sucesso');
        log_acao('ALTERNAR_STATUS_RECEPCIONISTA', $_SESSION['id_cliente'], "ID: $id -> $novo");
    }

    redirect('backend/views/painel_admin.php?acao=recepcionistas');
}

// ====== EXCLUIR ======
elseif ($acao === 'excluir_recepcionista') {
    $id = intval($_POST['id_recepcionista'] ?? 0);

    $stmt = $db->prepare('DELETE FROM clientes WHERE id=? AND tipo="recepcionista"');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    set_flash_message('recepcionista', 'Recepcionista excluída com sucesso', 'sucesso');
    log_acao('EXCLUIR_RECEPCIONISTA', $_SESSION['id_cliente'], "ID: $id");
    redirect('backend/views/painel_admin.php?acao=recepcionistas');
}

$_SESSION['erros_recepcionista'] = ['Ação não permitida'];
redirect('backend/views/painel_admin.php?acao=recepcionistas');
?>
