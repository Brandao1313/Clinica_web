<?php
// ====================================================
// ARQUIVO: backend/controllers/cadastrar_paciente_rapido.php
// Descrição: Endpoint AJAX — cadastro rápido de paciente pela recepcionista
// Retorno: JSON { sucesso: bool, id?: int, nome?: string, erros?: string[] }
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_autenticado() || !is_recepcionista()) {
    echo json_encode(['sucesso' => false, 'erros' => ['Não autorizado.']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erros' => ['Método inválido.']]);
    exit;
}

$token_csrf = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($token_csrf)) {
    echo json_encode(['sucesso' => false, 'erros' => ['Token de segurança inválido.']]);
    exit;
}

$conexao_db = Conexao::getInstance()->getConexao();

$nome            = sanitizar_input($_POST['nome'] ?? '');
$data_nascimento = sanitizar_input($_POST['data_nascimento'] ?? '');
$cpf             = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$telefone        = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$email           = sanitizar_input($_POST['email'] ?? '');

$erros = [];

if (strlen($nome) < 2)           $erros[] = 'Nome completo é obrigatório.';
if (empty($data_nascimento))      $erros[] = 'Data de nascimento é obrigatória.';
if (strlen($cpf) !== 11)          $erros[] = 'CPF inválido — informe os 11 dígitos.';
if (strlen($telefone) < 10)       $erros[] = 'Telefone inválido.';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

if (!empty($erros)) {
    echo json_encode(['sucesso' => false, 'erros' => $erros]);
    exit;
}

// Verificar CPF duplicado
$stmt = $conexao_db->prepare("SELECT id FROM clientes WHERE cpf = ?");
$stmt->bind_param('s', $cpf);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(['sucesso' => false, 'erros' => ['CPF já cadastrado no sistema.']]);
    $stmt->close();
    exit;
}
$stmt->close();

// Verificar telefone duplicado
$stmt = $conexao_db->prepare("SELECT id FROM clientes WHERE telefone = ?");
$stmt->bind_param('s', $telefone);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(['sucesso' => false, 'erros' => ['Telefone já cadastrado no sistema.']]);
    $stmt->close();
    exit;
}
$stmt->close();

// Gerar e-mail placeholder se não informado
if (empty($email)) {
    $email = 'sem_email_' . $cpf . '@clinica.local';
}

$senha_hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

$stmt = $conexao_db->prepare(
    "INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, tipo, ativo)
     VALUES (?, ?, ?, ?, ?, ?, 'cliente', 1)"
);
$stmt->bind_param('ssssss', $nome, $email, $cpf, $telefone, $data_nascimento, $senha_hash);

if ($stmt->execute()) {
    $id_novo = $conexao_db->insert_id;
    log_acao('RECEP_CADASTRO_RAPIDO', $_SESSION['id_cliente'], "Paciente: $nome | CPF: $cpf | ID: $id_novo");
    echo json_encode(['sucesso' => true, 'id' => $id_novo, 'nome' => $nome]);
} else {
    echo json_encode(['sucesso' => false, 'erros' => ['Erro ao cadastrar. Verifique CPF e telefone.']]);
}
$stmt->close();
exit;
