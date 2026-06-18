<?php
// ====================================================
// ARQUIVO: backend/utils/seguranca.php
// Descrição: Funções de segurança (hash, CSRF, etc)
// ====================================================

/**
 * Gerar hash seguro de senha com bcrypt
 * @param string $senha
 * @return string
 */
function gerar_hash_senha($senha) {
    // password_hash usa bcrypt por padrão e é seguro contra ataques de timing
    return password_hash($senha, PASSWORD_BCRYPT, [
        'cost' => 10 // Custo computacional (quanto maior, mais seguro mas mais lento)
    ]);
}

/**
 * Verificar se senha corresponde ao hash
 * @param string $senha
 * @param string $hash
 * @return bool
 */
function verificar_senha($senha, $hash) {
    return password_verify($senha, $hash);
}

/**
 * Gerar token CSRF (Cross-Site Request Forgery)
 * @return string
 */
function gerar_token_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 * @param string $token
 * @return bool
 */
function validar_token_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gerar token aleatório para reset de senha
 * @return string
 */
function gerar_token_aleatorio($tamanho = 32) {
    return bin2hex(random_bytes($tamanho));
}

/**
 * Sanitizar output para exibição (previne XSS)
 * @param string $string
 * @return string
 */
function sanitizar_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Verificar se usuário está autenticado
 * @return bool
 */
function is_autenticado() {
    return isset($_SESSION['id_cliente']) && !empty($_SESSION['id_cliente']);
}

function is_admin() {
    return is_autenticado() && ($_SESSION['tipo_usuario'] ?? '') === 'admin';
}

function is_medico() {
    return is_autenticado() && ($_SESSION['tipo_usuario'] ?? '') === 'medico';
}

function is_recepcionista() {
    return is_autenticado() && ($_SESSION['tipo_usuario'] ?? '') === 'recepcionista';
}

function require_cliente() {
    require_login();
    $tipo = $_SESSION['tipo_usuario'] ?? 'cliente';
    switch ($tipo) {
        case 'admin':         redirect('backend/views/painel_admin.php');
        case 'medico':        redirect('backend/views/painel_medico.php');
        case 'recepcionista': redirect('backend/views/painel_recepcionista.php');
    }
}

function require_medico() {
    require_login();
    if (!is_medico()) {
        redirect('backend/views/painel_cliente.php');
    }
}

function require_recepcionista() {
    require_login();
    if (!is_recepcionista()) {
        redirect('backend/views/painel_cliente.php');
    }
}

/**
 * Redirecionar se não autenticado
 * @param string $pagina_retorno
 * @return void
 */
function require_login($pagina_retorno = null) {
    if (!is_autenticado()) {
        $_SESSION['mensagem_erro'] = ERRO_NAO_AUTENTICADO;
        if ($pagina_retorno) {
            $_SESSION['pagina_retorno'] = $pagina_retorno;
        }
        header('Location: ' . SITE_URL . '/cadastro/login.php');
        exit;
    }
}

/**
 * Redirecionar se não é admin
 * @return void
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['mensagem_erro'] = ERRO_NAO_ADMIN;
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Flash message - armazenar mensagem para exibir uma vez
 * @param string $chave
 * @param string $mensagem
 * @param string $tipo (sucesso, erro, aviso, info)
 * @return void
 */
function set_flash_message($chave, $mensagem, $tipo = 'info') {
    $_SESSION['flash_' . $chave] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo
    ];
}

/**
 * Obter flash message (e remover)
 * @param string $chave
 * @return array|null
 */
function get_flash_message($chave) {
    if (isset($_SESSION['flash_' . $chave])) {
        $mensagem = $_SESSION['flash_' . $chave];
        unset($_SESSION['flash_' . $chave]);
        return $mensagem;
    }
    return null;
}

/**
 * Gerar URL com redirecionamento seguro
 * @param string $url
 * @return string
 */
function url_segura($url) {
    // Verificar se URL é relativa e interna
    if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
        return SITE_URL . '/' . ltrim($url, '/');
    }
    return $url;
}

/**
 * Fazer redirect seguro
 * @param string $url
 * @return void
 */
function redirect($url) {
    header('Location: ' . url_segura($url));
    exit;
}

?>
