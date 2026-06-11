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

$acao = sanitizar_input($_POST['acao'] ?? '');
$erros = [];

// ====== VERIFICAÇÃO CSRF ======

$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    $_SESSION['erros_reset'] = ['Token de segurança inválido. Tente novamente.'];
    redirect('cadastro/esqueci_senha.php');
}

// Tempo de validade da autorização de redefinição (em segundos)
const RESET_AUTORIZACAO_SEGUNDOS = 300;

// ====== AÇÃO 1: VERIFICAR TELEFONE + CPF ======

if ($acao === 'verificar_telefone') {
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $mensagem_generica = 'Dados não conferem. Verifique o telefone e CPF.';

    if (empty($telefone) || empty($cpf)) {
        $erros[] = 'Telefone e CPF são obrigatórios';
    } elseif (!validar_telefone($telefone)) {
        $erros[] = ERRO_TELEFONE_INVALIDO;
    } elseif (strlen($cpf) !== 11) {
        $erros[] = ERRO_CPF_INVALIDO;
    } elseif (!verificar_limite_tentativas_reset($telefone)) {
        log_seguranca('RESET_SENHA_LIMITE_EXCEDIDO', "Telefone: $telefone");
        $erros[] = 'Muitas tentativas. Tente novamente mais tarde.';
    } else {
        $conexao_db = Conexao::getInstance()->getConexao();

        $stmt = $conexao_db->prepare('SELECT id FROM clientes WHERE telefone = ? AND cpf = ?');
        $stmt->bind_param('ss', $telefone, $cpf);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            log_seguranca('RESET_SENHA_DADOS_NAO_CONFEREM', "Telefone: $telefone");
            $erros[] = $mensagem_generica;
        } else {
            $cliente = $result->fetch_assoc();
            $_SESSION['id_reset_autorizado'] = $cliente['id'];
            $_SESSION['id_reset_autorizado_exp'] = time() + RESET_AUTORIZACAO_SEGUNDOS;
            log_seguranca('RESET_SENHA_AUTORIZADO', "Telefone: $telefone | Cliente ID: {$cliente['id']}");
        }
        $stmt->close();
    }
}

// ====== AÇÃO 2: REDEFINIR SENHA (SEM TOKEN) ======

elseif ($acao === 'redefinir_sem_token') {
    $senha_nova = $_POST['senha_nova'] ?? '';
    $confirmacao_senha = $_POST['confirmacao_senha'] ?? '';

    $id_cliente = $_SESSION['id_reset_autorizado'] ?? null;
    $expiracao = $_SESSION['id_reset_autorizado_exp'] ?? 0;

    if (empty($id_cliente) || time() > $expiracao) {
        unset($_SESSION['id_reset_autorizado'], $_SESSION['id_reset_autorizado_exp']);
        log_seguranca('RESET_SENHA_AUTORIZACAO_EXPIRADA', '');
        $erros[] = 'Sessão de verificação expirada. Informe seus dados novamente.';
    } elseif (empty($senha_nova)) {
        $erros[] = 'Nova senha é obrigatória';
    } elseif (!validar_senha_forte($senha_nova)) {
        $erros[] = ERRO_SENHA_FRACA;
    } elseif ($senha_nova !== $confirmacao_senha) {
        $erros[] = ERRO_SENHAS_NAOCOMPAT;
    } else {
        $conexao_db = Conexao::getInstance()->getConexao();

        $hash_senha = gerar_hash_senha($senha_nova);

        $stmt = $conexao_db->prepare('UPDATE clientes SET senha_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash_senha, $id_cliente);

        if ($stmt->execute()) {
            unset($_SESSION['id_reset_autorizado'], $_SESSION['id_reset_autorizado_exp']);

            log_acao('RESET_SENHA_COMPLETADO', $id_cliente, 'Senha redefinida com sucesso');
            log_seguranca('RESET_SENHA_COMPLETADO', "Cliente ID: $id_cliente");
            set_flash_message('reset', 'Senha redefinida com sucesso! Faça login com sua nova senha.', 'sucesso');
            redirect('cadastro/login.php');
        } else {
            $erros[] = 'Erro ao atualizar senha. Tente novamente.';
        }
        $stmt->close();
    }
}

// Ação desconhecida
else {
    $erros[] = 'Ação não permitida';
}

// ====== RETORNAR RESULTADO ======

if (!empty($erros)) {
    $_SESSION['erros_reset'] = $erros;
    $_SESSION['telefone_reset'] = $telefone ?? '';
}

redirect('cadastro/esqueci_senha.php');

?>
