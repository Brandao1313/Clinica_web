<?php
// ====================================================
// ARQUIVO: cadastro/criar_conta.php
// Descrição: Página para criar nova conta
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

$base_url = '../';
$titulo_pagina = 'Criar Conta - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1>Criar conta</h1>
            <p>Preencha os dados para criar sua conta.</p>

            <?php if (isset($_SESSION['erros_cadastro']) && !empty($_SESSION['erros_cadastro'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['erros_cadastro'] as $erro): ?>
                        <p>❌ <?php echo htmlspecialchars($erro); ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['erros_cadastro']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../backend/auth/registrar.php">
                <label for="name">Nome completo *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['dados_cadastro']['name'] ?? ''); ?>" required>

                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['dados_cadastro']['email'] ?? ''); ?>" required>

                <label for="cpf">CPF (somente números) *</label>
                <input type="text" id="cpf" name="cpf" placeholder="12345678901" value="<?php echo htmlspecialchars($_SESSION['dados_cadastro']['cpf'] ?? ''); ?>" required>

                <label for="telefone">Telefone (opcional)</label>
                <input type="tel" id="telefone" name="telefone" placeholder="11999999999" value="<?php echo htmlspecialchars($_SESSION['dados_cadastro']['telefone'] ?? ''); ?>">

                <label for="data_nascimento">Data de nascimento (opcional)</label>
                <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($_SESSION['dados_cadastro']['data_nascimento'] ?? ''); ?>">

                <label for="password">Senha *</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm">Confirmar senha *</label>
                <input type="password" id="confirm" name="confirm" required>

                <div class="actions">
                    <button class="btn-primary" type="submit">Criar conta</button>
                    <a class="btn-secondary" href="login.php">Voltar ao login</a>
                </div>
            </form>

            <div class="note">Ao criar uma conta, você concorda com os nossos termos.</div>

            <?php unset($_SESSION['dados_cadastro']); ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
