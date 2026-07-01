<?php
// ====================================================
// ARQUIVO: cadastro/esqueci_senha.php
// Descrição: Página para redefinição de senha (verificação por telefone + CPF)
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/validacao.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

// Etapa 2 (definir nova senha) só é exibida se a verificação telefone+CPF
// foi concluída com sucesso e a autorização ainda não expirou
$autorizado = !empty($_SESSION['id_reset_autorizado']) && time() <= ($_SESSION['id_reset_autorizado_exp'] ?? 0);
$etapa = $autorizado ? 'redefinir' : 'verificar';

$base_url = '../';
$titulo_pagina = 'Redefinir Senha - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../backend/includes/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1>Redefinir Senha</h1>

            <?php
                $erros_reset = $_SESSION['erros_reset'] ?? [];
                unset($_SESSION['erros_reset']);
            ?>
            <?php if (!empty($erros_reset)): ?>
                <div class="flash-container" role="status" aria-live="polite">
                    <?php foreach ($erros_reset as $erro): ?>
                        <div class="flash-toast flash-erro">
                            <span class="flash-toast-icone"><i class="fa-solid fa-circle-xmark"></i></span>
                            <span class="flash-toast-texto"><?php echo htmlspecialchars($erro); ?></span>
                            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                            <span class="flash-toast-progresso"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($etapa === 'verificar'): ?>
                <!-- ETAPA 1: Verificar telefone + CPF -->
                <p>Informe o telefone e o CPF cadastrados na sua conta para definir uma nova senha.</p>
                <form method="POST" action="../backend/auth/redefinir_senha.php">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <input type="hidden" name="acao" value="verificar_telefone">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-mobile-screen"></i> Identificação</div>
                        <label for="telefone">Telefone *</label>
                        <input type="tel" id="telefone" name="telefone" placeholder="11999999999" value="<?php echo htmlspecialchars($_SESSION['telefone_reset'] ?? ''); ?>" required>

                        <label for="cpf">CPF *</label>
                        <input type="text" id="cpf" name="cpf" placeholder="12345678901" required>
                    </div>

                    <div class="actions">
                        <button class="btn-primary" type="submit">Continuar</button>
                        <a class="btn-secondary" href="login.php">Voltar ao login</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- ETAPA 2: Definir nova senha -->
                <p>Dados confirmados! Defina sua nova senha abaixo.</p>
                <form method="POST" action="../backend/auth/redefinir_senha.php">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <input type="hidden" name="acao" value="redefinir_sem_token">

                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-lock"></i> Nova senha</div>
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

            <?php unset($_SESSION['telefone_reset']); ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../backend/includes/footer.php'; ?>
