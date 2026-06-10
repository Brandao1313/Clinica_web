<?php
// ====================================================
// ARQUIVO: cadastro/esqueci_senha.php
// Descrição: Página para redefinição de senha
// ====================================================

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/utils/seguranca.php';

$token = sanitizar_input($_GET['token'] ?? '');
$etapa = empty($token) ? 'solicitar' : 'redefinir';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Clínica</title>
    <style>
        * { box-sizing: border-box; margin:0; padding:0 }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg,#81f1d5 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center }
        .card { background:#fff; padding:32px; border-radius:8px; box-shadow:0 8px 20px rgba(0,0,0,0.15); width:100%; max-width:450px }
        h1{ color:#0e9c1e; margin-bottom:8px }
        p{ color:#555; margin-bottom:18px }
        label{ display:block; margin-bottom:8px; color:#333 }
        input{ width:100%; padding:12px; border:2px solid #e0e0e0; border-radius:6px; margin-bottom:12px }
        .btn{ padding:10px 14px; border-radius:6px; text-decoration:none; display:inline-block; border:none; cursor:pointer; font-weight:500; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff }
        .btn-secondary{ background:#f0f0f0; color:#333 }
        .btn:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,0.4) }
        .alert { padding:12px; border-radius:6px; margin-bottom:15px; border:1px solid }
        .alert-danger { background:#f8d7da; border-color:#f5c6cb; color:#721c24 }
        .alert-success { background:#d4edda; border-color:#c3e6cb; color:#155724 }
        .alert p { margin:5px 0 }
        .actions { margin-top:12px; display:flex; gap:10px; flex-wrap:wrap }
    </style>
</head>

<body>
    <div class="card">
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
                    <button class="btn" type="submit">Enviar link</button>
                    <a class="btn btn-secondary" href="login.php">Voltar ao login</a>
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
                    <button class="btn" type="submit">Redefinir Senha</button>
                    <a class="btn btn-secondary" href="login.php">Voltar ao login</a>
                </div>
            </form>
        <?php endif; ?>

        <?php unset($_SESSION['email_reset']); ?>
    </div>
</body>

</html>
