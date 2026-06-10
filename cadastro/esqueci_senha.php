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

            <?php
                $erros_reset = $_SESSION['erros_reset'] ?? [];
                unset($_SESSION['erros_reset']);
                $sucesso_reset = $_SESSION['sucesso_reset'] ?? null;
                unset($_SESSION['sucesso_reset']);
            ?>
            <?php if (!empty($erros_reset) || $sucesso_reset): ?>
                <div class="flash-container" role="status" aria-live="polite">
                    <?php foreach ($erros_reset as $erro): ?>
                        <div class="flash-toast flash-erro">
                            <span class="flash-toast-icone">❌</span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($erro); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($sucesso_reset): ?>
                        <div class="flash-toast flash-sucesso">
                            <span class="flash-toast-icone">✅</span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($sucesso_reset); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($etapa === 'solicitar'): ?>
                <!-- ETAPA 1: Solicitar redefinição -->
                <p>Digite seu email para receber um link de redefinição de senha.</p>
                <form method="POST" action="../backend/auth/redefinir_senha.php">
                    <input type="hidden" name="acao" value="solicitar">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo">📧 Identificação</div>
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_reset'] ?? ''); ?>" required>
                    </div>

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

                    <div class="form-grupo">
                        <div class="form-grupo-titulo">🔒 Nova senha</div>
                        <label for="senha_nova">Nova Senha *</label>
                        <input type="password" id="senha_nova" name="senha_nova" required>

                        <label for="confirmacao_senha">Confirmar Senha *</label>
                        <input type="password" id="confirmacao_senha" name="confirmacao_senha" required>
                    </div>

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
