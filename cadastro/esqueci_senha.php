<?php
// ====================================================
// ARQUIVO: cadastro/esqueci_senha.php
// Descrição: Página para redefinição de senha
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

$token = sanitizar_input($_GET['token'] ?? '');
$etapa = empty($token) ? 'solicitar' : 'redefinir';

$base_url = '../';
$titulo_pagina = 'Redefinir Senha - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1>Redefinir Senha</h1>

            <?php if (isset($_SESSION['erros_reset']) && !empty($_SESSION['erros_reset'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['erros_reset'] as $erro): ?>
                        <p>❌ <?php echo htmlspecialchars($erro); ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['erros_reset']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['sucesso_reset'])): ?>
                <div class="alert alert-success">
                    <p>✅ <?php echo htmlspecialchars($_SESSION['sucesso_reset']); ?></p>
                    <?php unset($_SESSION['sucesso_reset']); ?>
                </div>
            <?php endif; ?>

            <?php if ($etapa === 'solicitar'): ?>
                <!-- ETAPA 1: Solicitar redefinição -->
                <p>Digite seu email para receber um link de redefinição de senha.</p>
                <form method="POST" action="../backend/auth/redefinir_senha.php">
                    <input type="hidden" name="acao" value="solicitar">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_reset'] ?? ''); ?>" required>

                    <div class="actions">
                        <button class="btn-primary" type="submit">Enviar link</button>
                        <a class="btn-secondary" href="login.php">Voltar ao login</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- ETAPA 2: Redefinir com token -->
                <p>Preencha sua nova senha abaixo.</p>
                <form method="POST" action="../backend/auth/redefinir_senha.php">
                    <input type="hidden" name="acao" value="redefinir">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <label for="senha_nova">Nova Senha *</label>
                    <input type="password" id="senha_nova" name="senha_nova" required>

                    <label for="confirmacao_senha">Confirmar Senha *</label>
                    <input type="password" id="confirmacao_senha" name="confirmacao_senha" required>

                    <div class="actions">
                        <button class="btn-primary" type="submit">Redefinir Senha</button>
                        <a class="btn-secondary" href="login.php">Voltar ao login</a>
                    </div>
                </form>
            <?php endif; ?>

            <?php unset($_SESSION['email_reset']); ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
