<?php
// ====================================================
// ARQUIVO: cadastro/login.php
// Descrição: Página de login
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

$base_url = '../';
$titulo_pagina = 'Login - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1>Entrar</h1>
            <h2>Faça login para acessar sua conta</h2>

            <?php if (isset($_SESSION['erros_login']) && !empty($_SESSION['erros_login'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['erros_login'] as $erro): ?>
                        <p>❌ <?php echo htmlspecialchars($erro); ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['erros_login']); ?>
                </div>
            <?php endif; ?>

            <?php
                $flash = get_flash_message('registro');
                if ($flash):
            ?>
                <div class="alert alert-success">
                    <p>✅ <?php echo htmlspecialchars($flash['mensagem']); ?></p>
                </div>
            <?php endif; ?>

            <?php
                $flash_logout = get_flash_message('logout');
                if ($flash_logout):
            ?>
                <div class="alert alert-success">
                    <p>✅ <?php echo htmlspecialchars($flash_logout['mensagem']); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="../backend/auth/logar.php">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_login'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="button-group">
                    <button type="submit">Entrar</button>
                    <a class="link-button" href="esqueci_senha.php">Esqueci minha senha</a>
                    <a class="link-button" href="criar_conta.php">Criar conta</a>
                </div>
            </form>

            <?php unset($_SESSION['email_login']); ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
