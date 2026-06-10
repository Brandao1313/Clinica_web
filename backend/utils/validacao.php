<?php
// ====================================================
// ARQUIVO: backend/utils/validacao.php
// Descrição: Funções de validação de dados
// ====================================================

/**
 * Validar CPF (verificar dígitos verificadores)
 * @param string $cpf
 * @return bool
 */
function validar_cpf($cpf) {
    // Remover caracteres especiais
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);

    // Verificar se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verificar se todos os dígitos são iguais (número inválido)
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcular primeiro dígito verificador
    for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;

    // Calcular segundo dígito verificador
    for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    // Verificar se os dígitos conferem
    return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
}

/**
 * Validar email
 * @param string $email
 * @return bool
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar telefone (formato brasileira: 11 números com DDD)
 * @param string $telefone
 * @return bool
 */
function validar_telefone($telefone) {
    $telefone = preg_replace('/[^0-9]/is', '', $telefone);
    return strlen($telefone) >= 10 && strlen($telefone) <= 11;
}

/**
 * Validar data no formato YYYY-MM-DD
 * @param string $data
 * @return bool
 */
function validar_data($data) {
    $formato = 'Y-m-d';
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}

/**
 * Validar força da senha
 * @param string $senha
 * @return bool
 */
function validar_senha_forte($senha) {
    // Mínimo de caracteres definido em config.php
    if (strlen($senha) < PASSWORD_MIN_LENGTH) {
        return false;
    }

    // Recomendações adicionais (opcional):
    // - Pelo menos um número
    // - Pelo menos uma letra maiúscula
    // - Pelo menos uma letra minúscula

    $temNumero = preg_match('/[0-9]/', $senha);
    $temLetraMaiuscula = preg_match('/[A-Z]/', $senha);
    $temLetraMinuscula = preg_match('/[a-z]/', $senha);

    // Para agora, apenas verificar tamanho mínimo
    // Pode descomentar as linhas abaixo para exigir padrão mais forte:
    // return $temNumero && $temLetraMaiuscula && $temLetraMinuscula;

    return true;
}

/**
 * Sanitizar entrada de texto
 * @param string $input
 * @return string
 */
function sanitizar_input($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Validar nome (apenas letras, números, espaços e hífens)
 * @param string $nome
 * @return bool
 */
function validar_nome($nome) {
    return preg_match('/^[a-záéíóúâêôãõç\s\-\']+$/i', $nome) && strlen($nome) >= 3;
}

/**
 * Validar data de nascimento (não pode ser menor que 18 anos)
 * @param string $data_nascimento (formato: YYYY-MM-DD)
 * @return bool
 */
function validar_idade_minima($data_nascimento, $idade_minima = 18) {
    try {
        $data = new DateTime($data_nascimento);
        $hoje = new DateTime();
        $idade = $hoje->diff($data)->y;
        return $idade >= $idade_minima;
    } catch (Exception $e) {
        return false;
    }
}

?>
