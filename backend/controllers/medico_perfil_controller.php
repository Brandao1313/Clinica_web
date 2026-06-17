<?php
// ====================================================
// ARQUIVO: backend/controllers/medico_perfil_controller.php
// Descrição: Ações do médico sobre seu próprio perfil
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_medico();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $_SESSION['erros_perfil_medico'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('backend/views/painel_medico.php?acao=perfil');
}

$acao = sanitizar_input($_POST['acao'] ?? '');

// ====== ALTERAR SENHA ======
if ($acao === 'alterar_senha_medico') {
    $senha_atual      = $_POST['senha_atual'] ?? '';
    $senha_nova       = $_POST['senha_nova'] ?? '';
    $confirmacao      = $_POST['confirmacao_senha'] ?? '';
    $id_medico        = $_SESSION['id_medico'];
    $erros            = [];

    $conexao_db = Conexao::getInstance()->getConexao();

    // Buscar hash atual
    $stmt = $conexao_db->prepare('SELECT senha_hash FROM medicos WHERE id = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !verificar_senha($senha_atual, $row['senha_hash'])) {
        $erros[] = 'Senha atual incorreta.';
    } elseif (!validar_senha_forte($senha_nova)) {
        $erros[] = ERRO_SENHA_FRACA;
    } elseif ($senha_nova !== $confirmacao) {
        $erros[] = ERRO_SENHAS_NAOCOMPAT;
    } else {
        $novo_hash = gerar_hash_senha($senha_nova);
        $stmt = $conexao_db->prepare('UPDATE medicos SET senha_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $novo_hash, $id_medico);
        $stmt->execute();
        $stmt->close();

        log_acao('MEDICO_ALTERAR_SENHA', $id_medico, 'Senha alterada pelo médico');
        set_flash_message('perfil_medico', 'Senha alterada com sucesso!', 'sucesso');
        redirect('backend/views/painel_medico.php?acao=perfil');
    }

    $_SESSION['erros_perfil_medico'] = $erros;
    redirect('backend/views/painel_medico.php?acao=perfil');
}

redirect('backend/views/painel_medico.php');
?>
