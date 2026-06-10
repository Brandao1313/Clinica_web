<?php
// ====================================================
// ARQUIVO: backend/auth/registrar.php
// Descrição: Processa criação de nova conta
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar se já está logado
if (is_autenticado()) {
    redirect('index.html');
}

// Processar apenas se for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Inicializar variáveis
$nome = sanitizar_input($_POST['name'] ?? '');
$email = sanitizar_input($_POST['email'] ?? '');
$telefone = sanitizar_input($_POST['telefone'] ?? '');
$data_nascimento = sanitizar_input($_POST['data_nascimento'] ?? '');
$cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
$senha = $_POST['password'] ?? '';
$confirmacao_senha = $_POST['confirm'] ?? '';

$erros = [];

// ====== VALIDAÇÕES ======

// Validar nome
if (empty($nome)) {
    $erros[] = 'Nome é obrigatório';
} elseif (!validar_nome($nome)) {
    $erros[] = 'Nome inválido (mínimo 3 caracteres, apenas letras)';
}

// Validar email
if (empty($email)) {
    $erros[] = 'Email é obrigatório';
} elseif (!validar_email($email)) {
    $erros[] = ERRO_EMAIL_INVALIDO;
}

// Validar CPF
if (empty($cpf)) {
    $erros[] = 'CPF é obrigatório';
} elseif (!validar_cpf($cpf)) {
    $erros[] = ERRO_CPF_INVALIDO;
}

// Validar telefone
if (!empty($telefone) && !validar_telefone($telefone)) {
    $erros[] = 'Telefone inválido';
}

// Validar data de nascimento
if (!empty($data_nascimento)) {
    if (!validar_data($data_nascimento)) {
        $erros[] = 'Data de nascimento inválida';
    } elseif (!validar_idade_minima($data_nascimento, 18)) {
        $erros[] = 'Você deve ter pelo menos 18 anos';
    }
}

// Validar senha
if (empty($senha)) {
    $erros[] = 'Senha é obrigatória';
} elseif (!validar_senha_forte($senha)) {
    $erros[] = ERRO_SENHA_FRACA;
}

// Validar confirmação de senha
if ($senha !== $confirmacao_senha) {
    $erros[] = ERRO_SENHAS_NAOCOMPAT;
}

// ====== VERIFICAR DUPLICATAS ======

// Se passou nas validações, verificar banco de dados
if (empty($erros)) {
    $conexao_db = Conexao::getInstance()->getConexao();

    // Verificar email único
    $stmt = $conexao_db->prepare('SELECT id FROM clientes WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $erros[] = ERRO_EMAIL_EXISTE;
    }
    $stmt->close();

    // Verificar CPF único
    $stmt = $conexao_db->prepare('SELECT id FROM clientes WHERE cpf = ?');
    $stmt->bind_param('s', $cpf);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $erros[] = ERRO_CPF_EXISTE;
    }
    $stmt->close();
}

// ====== SALVAR NO BANCO DE DADOS ======

if (empty($erros)) {
    $conexao_db = Conexao::getInstance()->getConexao();

    // Gerar hash da senha
    $hash_senha = gerar_hash_senha($senha);

    // Preparar dados para inserção
    $stmt = $conexao_db->prepare(
        'INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, ativo, eh_admin)
         VALUES (?, ?, ?, ?, ?, ?, 1, 0)'
    );

    if (!$stmt) {
        $erros[] = 'Erro ao preparar inserção: ' . $conexao_db->error;
    } else {
        $stmt->bind_param(
            'ssssss',
            $nome,
            $email,
            $cpf,
            $telefone,
            $data_nascimento,
            $hash_senha
        );

        if ($stmt->execute()) {
            // Sucesso! Redirecionar para login com mensagem
            set_flash_message('registro', SUCESSO_CADASTRO, 'sucesso');
            log_acao('CADASTRO_USUARIO', 0, "Email: $email");
            redirect('login.html');
        } else {
            $erros[] = 'Erro ao criar conta: ' . $conexao_db->error;
        }
        $stmt->close();
    }
}

// ====== RETORNAR ERROS ======

// Armazenar erros na sessão para exibição
if (!empty($erros)) {
    $_SESSION['erros_cadastro'] = $erros;
    $_SESSION['dados_cadastro'] = [
        'name' => $nome,
        'email' => $email,
        'cpf' => $cpf,
        'telefone' => $telefone,
        'data_nascimento' => $data_nascimento
    ];
}

// Redirecionar de volta para formulário
redirect('criar_conta.html');

?>
