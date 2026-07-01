<?php
// ====================================================
// ARQUIVO: backend/auth/deslogar.php
// Descrição: Faz logout do usuário
// ====================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar se está logado
if (is_autenticado()) {
    $id_usuario = $_SESSION['id_cliente'];
    log_acao('LOGOUT', $id_usuario, 'Logout realizado');
}

// Limpar sessão
session_destroy();

// Mensagem de sucesso
set_flash_message('logout', SUCESSO_LOGOUT, 'sucesso');

// Redirecionar para index
redirect('index.php');

?>
