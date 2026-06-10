<?php
// ====================================================
// ARQUIVO: backend/auth/redefinir_senha.php
// Descrição: Processa recuperação e redefinição de senha
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Processar apenas se for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$acao = sanitizar_input($_POST['acao'] ?? 'solicitar');
$erros = [];
$sucesso = '';

// ====== AÇÃO 1: SOLICITAR REDEFINIÇÃO ======

if ($acao === 'solicitar') {
    $email = sanitizar_input($_POST['email'] ?? '');

    if (empty($email)) {
        $erros[] = 'Email é obrigatório';
    } elseif (!validar_email($email)) {
        $erros[] = ERRO_EMAIL_INVALIDO;
    } else {
        $conexao_db = Conexao::getInstance()->getConexao();

        // Verificar se email existe
        $stmt = $conexao_db->prepare('SELECT id, nome FROM clientes WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Por segurança, não revelar se email existe ou não
            $sucesso = 'Se o email existir em nosso sistema, você receberá um link de redefinição.';
        } else {
            $cliente = $result->fetch_assoc();
            $id_cliente = $cliente['id'];
            $nome_cliente = $cliente['nome'];

            // Gerar token
            $token = gerar_token_aleatorio();
            $data_expiracao = gerar_expiracao_token();

            // Salvar token no banco de dados
            $stmt2 = $conexao_db->prepare(
                'INSERT INTO redefinicao_senha (id_cliente, token, data_expiracao) VALUES (?, ?, ?)'
            );
            $stmt2->bind_param('iss', $id_cliente, $token, $data_expiracao);

            if ($stmt2->execute()) {
                // Simular envio de email (em produção, usar SwiftMailer ou similar)
                $link_reset = SITE_URL . '/backend/auth/redefinir_senha.php?token=' . $token;
                $mensagem_email = "Olá $nome_cliente,\n\n";
                $mensagem_email .= "Para redefinir sua senha, clique no link abaixo:\n";
                $mensagem_email .= "$link_reset\n\n";
                $mensagem_email .= "Este link expira em 24 horas.\n\n";
                $mensagem_email .= "Se você não solicitou esta redefinição, ignore este email.\n";

                // Log do "envio" de email (em arquivo)
                error_log("EMAIL DE RECUPERAÇÃO:\nPara: $email\nMensagem:\n$mensagem_email\n\n", 3, __DIR__ . '/../../logs/emails.log');

                log_acao('SOLICITAR_RESET_SENHA', $id_cliente, "Email: $email");
                $sucesso = 'Se o email existir em nosso sistema, você receberá um link de redefinição.';
            } else {
                $erros[] = 'Erro ao processar solicitação. Tente novamente.';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

// ====== AÇÃO 2: VALIDAR TOKEN E REDEFINIR SENHA ======

elseif ($acao === 'redefinir') {
    $token = sanitizar_input($_POST['token'] ?? '');
    $senha_nova = $_POST['senha_nova'] ?? '';
    $confirmacao_senha = $_POST['confirmacao_senha'] ?? '';

    if (empty($token)) {
        $erros[] = 'Token inválido';
    } elseif (empty($senha_nova)) {
        $erros[] = 'Nova senha é obrigatória';
    } elseif (!validar_senha_forte($senha_nova)) {
        $erros[] = ERRO_SENHA_FRACA;
    } elseif ($senha_nova !== $confirmacao_senha) {
        $erros[] = ERRO_SENHAS_NAOCOMPAT;
    } else {
        $conexao_db = Conexao::getInstance()->getConexao();

        // Verificar token
        $stmt = $conexao_db->prepare(
            'SELECT id_cliente, usado FROM redefinicao_senha WHERE token = ? AND data_expiracao > datetime("now", "localtime") AND usado = 0'
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $erros[] = 'Token inválido ou expirado';
        } else {
            $token_info = $result->fetch_assoc();
            $id_cliente = $token_info['id_cliente'];

            // Gerar novo hash da senha
            $hash_senha = gerar_hash_senha($senha_nova);

            // Atualizar senha no banco
            $stmt2 = $conexao_db->prepare('UPDATE clientes SET senha_hash = ? WHERE id = ?');
            $stmt2->bind_param('si', $hash_senha, $id_cliente);

            if ($stmt2->execute()) {
                // Marcar token como usado
                $stmt3 = $conexao_db->prepare('UPDATE redefinicao_senha SET usado = 1 WHERE token = ?');
                $stmt3->bind_param('s', $token);
                $stmt3->execute();
                $stmt3->close();

                log_acao('RESET_SENHA_COMPLETADO', $id_cliente, 'Senha redefinida com sucesso');
                set_flash_message('reset', 'Senha redefinida com sucesso! Faça login com sua nova senha.', 'sucesso');
                redirect('cadastro/login.php');
            } else {
                $erros[] = 'Erro ao atualizar senha. Tente novamente.';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

// ====== RETORNAR RESULTADO ======

if (!empty($erros)) {
    $_SESSION['erros_reset'] = $erros;
    $_SESSION['email_reset'] = $email ?? '';
}

if (!empty($sucesso)) {
    $_SESSION['sucesso_reset'] = $sucesso;
}

redirect('cadastro/esqueci_senha.php');

?>
