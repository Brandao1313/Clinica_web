<?php
// ====================================================
// ARQUIVO: includes/header.php
// Descrição: Cabeçalho e navbar compartilhados
//
// Variáveis esperadas (definidas antes do include):
//   $base_url      - prefixo relativo até a raiz do projeto (ex: '', '../', '../../')
//   $titulo_pagina - título da aba do navegador
// ====================================================

require_once __DIR__ . '/../backend/config/conexao.php';
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

if (!isset($base_url)) {
    $base_url = '';
}
if (!isset($titulo_pagina)) {
    $titulo_pagina = 'Clínica Saúde & Bem-Estar';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_pagina); ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/estilo.css">
</head>
<body data-base-url="<?php echo $base_url; ?>">
    <nav>
        <ul>
            <li><a href="<?php echo $base_url; ?>index.php"><img src="<?php echo $base_url; ?>imagens/logo.png" alt="Logo da Clínica Saúde & Bem-Estar"></a></li>
            <li><a href="<?php echo $base_url; ?>especialidades.php">Especialidades</a></li>
            <li><a href="<?php echo $base_url; ?>medicos.php">Médicos</a></li>
            <li><a href="<?php echo $base_url; ?>exames.php">Exames</a></li>
            <li><a href="<?php echo $base_url; ?>sobre.php">Sobre</a></li>
            <?php if (is_autenticado()): ?>
                <li><a href="<?php echo $base_url; ?>backend/views/painel_cliente.php">Meu Painel</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="<?php echo $base_url; ?>backend/views/painel_admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="<?php echo $base_url; ?>backend/auth/deslogar.php" class="meu-btn">Sair (<?php echo htmlspecialchars($_SESSION['nome_cliente']); ?>)</a></li>
            <?php else: ?>
                <li><a href="<?php echo $base_url; ?>cadastro/login.php" class="meu-btn">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
