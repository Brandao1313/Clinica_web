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

            <?php
                $erros_login = $_SESSION['erros_login'] ?? [];
                unset($_SESSION['erros_login']);
                $flash = get_flash_message('registro');
                $flash_logout = get_flash_message('logout');
            ?>
            <?php if (!empty($erros_login) || $flash || $flash_logout): ?>
                <div class="flash-container" role="status" aria-live="polite">
                    <?php foreach ($erros_login as $erro): ?>
                        <div class="flash-toast flash-erro">
                            <span class="flash-toast-icone">❌</span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($erro); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($flash): ?>
                        <div class="flash-toast flash-sucesso">
                            <span class="flash-toast-icone">✅</span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($flash['mensagem']); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flash_logout): ?>
                        <div class="flash-toast flash-sucesso">
                            <span class="flash-toast-icone">✅</span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_logout['mensagem']); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../backend/auth/logar.php">
                <div class="form-grupo">
                    <div class="form-grupo-titulo">🔑 Dados de acesso</div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_login'] ?? ''); ?>" required>
                        <?php if (!empty($erros_login)): ?>
                            <div class="campo-erro">Verifique seu email e senha</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
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
