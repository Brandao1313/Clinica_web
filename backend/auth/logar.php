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

if (is_autenticado()) {
    redirect(_painel_por_tipo());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$email = sanitizar_input($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';
$erros = [];

// ====== CSRF ======
$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $erros[] = 'Token de segurança inválido. Tente novamente.';
}

// ====== RATE LIMITING ======
const MAX_TENTATIVAS_LOGIN  = 5;
const BLOQUEIO_LOGIN_MINUTOS = 15;

if (empty($erros)) {
    $bloqueio_ate = $_SESSION['login_bloqueio_ate'] ?? null;
    if ($bloqueio_ate && strtotime($bloqueio_ate) > time()) {
        $min = (int) ceil((strtotime($bloqueio_ate) - time()) / 60);
        $erros[] = "Muitas tentativas de login. Tente novamente em $min minuto(s).";
    }
}

if (empty($erros) && empty($email)) $erros[] = 'Email é obrigatório';
if (empty($erros) && empty($senha))  $erros[] = 'Senha é obrigatória';

// ====== CREDENCIAIS — única tabela: clientes, campo tipo ======
if (empty($erros)) {
    $conexao_db = Conexao::getInstance()->getConexao();

    $stmt = $conexao_db->prepare(
        'SELECT id, nome, email, senha_hash, ativo, tipo FROM clientes WHERE email = ?'
    );

    if (!$stmt) {
        $erros[] = 'Erro ao conectar: ' . $conexao_db->error;
    } else {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $erros[] = ERRO_LOGIN_INVALIDO;
        } else {
            $usuario = $result->fetch_assoc();
            $stmt->close();

            if ($usuario['ativo'] != 1) {
                $erros[] = 'Sua conta foi desativada. Entre em contato com suporte.';
            } elseif (!verificar_senha($senha, $usuario['senha_hash'])) {
                $erros[] = ERRO_LOGIN_INVALIDO;
            } else {
                // Montar sessão
                $_SESSION['id_cliente']    = $usuario['id'];
                $_SESSION['nome_cliente']  = $usuario['nome'];
                $_SESSION['email_cliente'] = $usuario['email'];
                $_SESSION['tipo_usuario']  = $usuario['tipo'];
                // Flags legadas (mantidas para compatibilidade)
                $_SESSION['eh_admin']          = $usuario['tipo'] === 'admin' ? 1 : 0;
                $_SESSION['eh_recepcionista']  = $usuario['tipo'] === 'recepcionista' ? 1 : 0;
                $_SESSION['eh_medico']         = $usuario['tipo'] === 'medico' ? 1 : 0;

                // Para médicos: buscar id na tabela medicos via id_cliente
                if ($usuario['tipo'] === 'medico') {
                    $s2 = $conexao_db->prepare('SELECT id FROM medicos WHERE id_cliente = ?');
                    $s2->bind_param('i', $usuario['id']);
                    $s2->execute();
                    $row = $s2->get_result()->fetch_assoc();
                    $s2->close();
                    $_SESSION['id_medico'] = $row['id'] ?? null;
                }

                unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio_ate']);
                log_acao('LOGIN', $usuario['id'], "tipo={$usuario['tipo']}");
                set_flash_message('login', SUCESSO_LOGIN, 'sucesso');
                redirect(_painel_por_tipo($usuario['tipo']));
            }
        }
        if (isset($stmt) && $result->num_rows === 0) $stmt->close();
    }
}

// ====== RATE LIMITING — tentativa falha ======
if (!empty($erros) && $erros !== ['Token de segurança inválido. Tente novamente.']) {
    $tentativas = ($_SESSION['login_tentativas'] ?? 0) + 1;
    $_SESSION['login_tentativas'] = $tentativas;
    if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
        $_SESSION['login_bloqueio_ate'] = date('Y-m-d H:i:s', strtotime('+' . BLOQUEIO_LOGIN_MINUTOS . ' minutes'));
        $_SESSION['login_tentativas'] = 0;
        log_acao('LOGIN_BLOQUEADO', 0, "Email: $email");
    }
}

if (!empty($erros)) {
    $_SESSION['erros_login'] = $erros;
    $_SESSION['email_login'] = $email;
}

redirect('cadastro/login.php');

// ====== HELPER ======
function _painel_por_tipo($tipo = null) {
    $tipo = $tipo ?? $_SESSION['tipo_usuario'] ?? 'cliente';
    switch ($tipo) {
        case 'admin':         return 'backend/views/painel_admin.php';
        case 'medico':        return 'backend/views/painel_medico.php';
        case 'recepcionista': return 'backend/views/painel_recepcionista.php';
        default:              return 'backend/views/painel_cliente.php';
    }
}
?>
