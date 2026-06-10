<?php
// ====================================================
// ARQUIVO: cadastro/criar_conta.php
// Descrição: Página para criar nova conta
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Clínica</title>
    <style>
        * { box-sizing: border-box; margin:0; padding:0 }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg,#81f1d5 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center }
        .card { background:#fff; padding:32px; border-radius:8px; box-shadow:0 8px 20px rgba(0,0,0,0.15); width:100%; max-width:480px; max-height:90vh; overflow-y:auto }
        h1{ color:#0e9c1e; margin-bottom:8px }
        p{ color:#555; margin-bottom:18px }
        label{ display:block; margin-bottom:8px; color:#333 }
        input{ width:100%; padding:12px; border:2px solid #e0e0e0; border-radius:6px; margin-bottom:12px }
        .actions{ margin-top:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap }
        .btn{ padding:10px 14px; border-radius:6px; text-decoration:none; display:inline-block; border:none; cursor:pointer; font-weight:500 }
        .btn-primary{ background:linear-gradient(135deg,#667eea,#764ba2); color:#fff }
        .btn-secondary{ background:#f0f0f0; color:#333 }
        .note{ font-size:13px; color:#666; margin-top:12px }
        .alert { padding:12px; border-radius:6px; margin-bottom:15px; border:1px solid }
        .alert-danger { background:#f8d7da; border-color:#f5c6cb; color:#721c24 }
        .alert-success { background:#d4edda; border-color:#c3e6cb; color:#155724 }
        .alert p { margin:5px 0 }
    </style>
</head>

<body>
    <div class="card">
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
                <button class="btn btn-primary" type="submit">Criar conta</button>
                <a class="btn btn-secondary" href="login.php">Voltar ao login</a>
            </div>
        </form>

        <div class="note">Ao criar uma conta, você concorda com os nossos termos.</div>

        <?php unset($_SESSION['dados_cadastro']); ?>
    </div>
</body>

</html>
