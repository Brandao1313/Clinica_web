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

// ====== VERIFICAÇÃO CSRF ======

$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $erros[] = 'Token de segurança inválido. Tente novamente.';
}

// ====== RATE LIMITING (5 tentativas / bloqueio de 15 minutos) ======

const MAX_TENTATIVAS_LOGIN = 5;
const BLOQUEIO_LOGIN_MINUTOS = 15;

if (empty($erros)) {
    $bloqueio_ate = $_SESSION['login_bloqueio_ate'] ?? null;
    if ($bloqueio_ate && strtotime($bloqueio_ate) > time()) {
        $minutos_restantes = (int) ceil((strtotime($bloqueio_ate) - time()) / 60);
        $erros[] = "Muitas tentativas de login. Tente novamente em $minutos_restantes minuto(s).";
    }
}

// ====== VALIDAÇÕES BÁSICAS ======

if (empty($erros) && empty($email)) {
    $erros[] = 'Email é obrigatório';
}

if (empty($erros) && empty($senha)) {
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

                // Resetar contador de tentativas de login
                unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);

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

// ====== REGISTRAR TENTATIVA MAL-SUCEDIDA (RATE LIMITING) ======

if (!empty($erros) && $erros !== ['Token de segurança inválido. Tente novamente.']) {
    $tentativas = ($_SESSION['login_tentativas'] ?? 0) + 1;
    $_SESSION['login_tentativas'] = $tentativas;

    if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
        $_SESSION['login_bloqueio_ate'] = date('Y-m-d H:i:s', strtotime("+" . BLOQUEIO_LOGIN_MINUTOS . " minutes"));
        $_SESSION['login_tentativas'] = 0;
        log_acao('LOGIN_BLOQUEADO', 0, "Email: $email");
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
