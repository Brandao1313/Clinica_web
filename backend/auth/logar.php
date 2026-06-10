<?php
// ====================================================
// ARQUIVO: backend/auth/logar.php
// Descrição: Processa login do usuário
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar se já está logado
if (is_autenticado()) {
    redirect('backend/views/painel_cliente.php');
}


// Processar apenas se for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Inicializar variáveis
$email = sanitizar_input($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

$erros = [];

// ====== VALIDAÇÕES BÁSICAS ======

if (empty($email)) {
    $erros[] = 'Email é obrigatório';
}

if (empty($senha)) {
    $erros[] = 'Senha é obrigatória';
}

// ====== VERIFICAR CREDENCIAIS ======

if (empty($erros)) {
    $conexao_db = Conexao::getInstance()->getConexao();

    // Buscar usuário por email
    $stmt = $conexao_db->prepare('SELECT id, nome, email, senha_hash, ativo, eh_admin FROM clientes WHERE email = ?');

    if (!$stmt) {
        $erros[] = 'Erro ao conectar: ' . $conexao_db->error;
    } else {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Usuário não encontrado
            $erros[] = ERRO_LOGIN_INVALIDO;
        } else {
            $usuario = $result->fetch_assoc();

            // Verificar se usuário está ativo
            if ($usuario['ativo'] != 1) {
                $erros[] = 'Sua conta foi desativada. Entre em contato com suporte.';
            } // Verificar senha
            elseif (!verificar_senha($senha, $usuario['senha_hash'])) {
                $erros[] = ERRO_LOGIN_INVALIDO;
            } else {
                // LOGIN BEM-SUCEDIDO!
                // Criar sessão do usuário
                $_SESSION['id_cliente'] = $usuario['id'];
                $_SESSION['nome_cliente'] = $usuario['nome'];
                $_SESSION['email_cliente'] = $usuario['email'];
                $_SESSION['eh_admin'] = $usuario['eh_admin'];

                // Log de atividade
                log_acao('LOGIN', $usuario['id'], 'Login realizado com sucesso');

                // Mensagem de sucesso
                set_flash_message('login', SUCESSO_LOGIN, 'sucesso');

                // Redirecionar para painel do cliente
                redirect('backend/views/painel_cliente.php');
            }
        }
        $stmt->close();
    }
}

// ====== RETORNAR ERROS ======

if (!empty($erros)) {
    $_SESSION['erros_login'] = $erros;
    $_SESSION['email_login'] = $email;
}

// Redirecionar de volta para formulário
redirect('cadastro/login.php');

?>
