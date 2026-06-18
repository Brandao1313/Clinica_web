<?php
// ====================================================
// ARQUIVO: backend/config/config.php
// Descrição: Configurações globais da aplicação
// ====================================================

// Caminho absoluto para a raiz do projeto (usado em require_once com BASE_PATH)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../..'));
}

// Configuração do banco de dados (SQLite)
// Caminho do arquivo do banco. Pode ser sobrescrito via variável de ambiente DB_PATH
define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/../../database/clinica.sqlite');

// URL base da aplicação (detectada automaticamente a partir do caminho do projeto)
if (!defined('SITE_URL')) {
    $site_url_env = getenv('SITE_URL');
    if ($site_url_env) {
        define('SITE_URL', $site_url_env);
    } elseif (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
        define('SITE_URL', 'http://localhost');
    } else {
        $raiz_fs = realpath(__DIR__ . '/../..');
        $doc_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $caminho_base = '';
        if ($doc_root && $raiz_fs && strpos($raiz_fs, $doc_root) === 0) {
            $caminho_base = str_replace('\\', '/', substr($raiz_fs, strlen($doc_root)));
        }
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        define('SITE_URL', $protocolo . '://' . $_SERVER['HTTP_HOST'] . $caminho_base);
    }
}

// ID do usuário administrador
define('ADMIN_ID', 1);

// Configurações de segurança
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_MAX_LENGTH', 255);

// Configurações de email (simulado)
define('EMAIL_FROM', 'noreply@clinica.com');
define('EMAIL_FROM_NAME', 'Clínica Saúde & Bem-Estar');

// Configuração de fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Habilitar exibição de erros apenas em desenvolvimento
// Comentar em produção
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Segurança: regenerar session ID em tempo regular
    if (!isset($_SESSION['última_regen'])) {
        $_SESSION['última_regen'] = time();
    }
    if (time() - $_SESSION['última_regen'] > 300) { // A cada 5 minutos
        session_regenerate_id(true);
        $_SESSION['última_regen'] = time();
    }
}

// Define constantes para mensagens de erro
define('ERRO_EMAIL_EXISTE', 'Email já cadastrado no sistema');
define('ERRO_CPF_EXISTE', 'CPF já cadastrado no sistema');
define('ERRO_CPF_INVALIDO', 'CPF inválido');
define('ERRO_EMAIL_INVALIDO', 'Email inválido');
define('ERRO_TELEFONE_EXISTE', 'Telefone já cadastrado no sistema');
define('ERRO_TELEFONE_INVALIDO', 'Telefone inválido');
define('ERRO_SENHA_FRACA', 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
define('ERRO_SENHAS_NAOCOMPAT', 'Senhas não conferem');
define('ERRO_LOGIN_INVALIDO', 'Email ou senha incorretos');
define('ERRO_NAO_AUTENTICADO', 'Você precisa fazer login para acessar esta página');
define('ERRO_NAO_ADMIN', 'Você não tem permissão para acessar esta página');

// Mensagens de sucesso
define('SUCESSO_CADASTRO', 'Conta criada com sucesso! Faça login para continuar');
define('SUCESSO_LOGIN', 'Login realizado com sucesso!');
define('SUCESSO_LOGOUT', 'Você saiu com sucesso');
define('SUCESSO_PERFIL_ATUALIZADO', 'Perfil atualizado com sucesso');
define('SUCESSO_AGENDAMENTO', 'Agendamento realizado com sucesso');
define('SUCESSO_CANCELAMENTO', 'Agendamento cancelado com sucesso');

?>
