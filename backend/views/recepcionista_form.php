<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/recepcionista_form.php
// Descrição: Formulário de cadastro/edição de recepcionista
//            - incluído por painel_admin.php
// ====================================================

$id = intval($_GET['id'] ?? 0);
$recepcionista = null;

if ($id > 0) {
    $stmt = $conexao_db->prepare('SELECT * FROM clientes WHERE id = ? AND tipo = "recepcionista"');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $recepcionista = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$recepcionista) {
        echo '<div class="alert alert-error">Recepcionista não encontrada.</div>';
        return;
    }
}

$dados = $_SESSION['dados_recepcionista'] ?? null;
unset($_SESSION['dados_recepcionista']);

$v = [
    'nome'     => $dados['nome']     ?? $recepcionista['nome']     ?? '',
    'email'    => $dados['email']    ?? $recepcionista['email']    ?? '',
    'cpf'      => $dados['cpf']      ?? ($recepcionista ? formatar_cpf($recepcionista['cpf']) : ''),
    'telefone' => $dados['telefone'] ?? ($recepcionista && !empty($recepcionista['telefone']) ? formatar_telefone($recepcionista['telefone']) : ''),
    'ativo'    => $dados['ativo']    ?? ($recepcionista['ativo'] ?? 1),
];
?>

<h3><?php echo $id > 0 ? 'Editar Recepcionista' : 'Nova Recepcionista'; ?></h3>

<?php if (!empty($_SESSION['erros_recepcionista'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_recepcionista'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_recepcionista']); ?>
<?php endif; ?>

<form method="POST" action="../controllers/admin_recepcionista_controller.php" style="max-width:560px;">
    <input type="hidden" name="acao" value="salvar_recepcionista">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
    <?php if ($id > 0): ?>
        <input type="hidden" name="id_recepcionista" value="<?php echo $id; ?>">
    <?php endif; ?>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-user"></i> Dados pessoais</div>

        <div style="margin-bottom:15px;">
            <label for="nome"><strong>Nome completo *</strong></label>
            <input type="text" id="nome" name="nome" required minlength="3" maxlength="150"
                   value="<?php echo htmlspecialchars($v['nome']); ?>"
                   style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
        </div>

        <div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:15px;">
            <div style="flex:1;min-width:200px;">
                <label for="email"><strong>E-mail *</strong></label>
                <input type="email" id="email" name="email" required maxlength="150"
                       value="<?php echo htmlspecialchars($v['email']); ?>"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
            <div style="flex:1;min-width:160px;">
                <label for="cpf"><strong>CPF *</strong></label>
                <input type="text" id="cpf" name="cpf" required maxlength="14" placeholder="000.000.000-00"
                       data-mascara="cpf"
                       value="<?php echo htmlspecialchars($v['cpf']); ?>"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
        </div>

        <div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:15px;">
            <div style="flex:1;min-width:180px;">
                <label for="telefone"><strong>Telefone</strong></label>
                <input type="tel" id="telefone" name="telefone" maxlength="15" placeholder="(11) 99999-9999"
                       data-mascara="telefone"
                       value="<?php echo htmlspecialchars($v['telefone']); ?>"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
            <div style="flex:1;min-width:100px;display:flex;align-items:center;gap:8px;padding-top:24px;">
                <input type="checkbox" id="ativo" name="ativo" value="1" <?php echo $v['ativo'] ? 'checked' : ''; ?>>
                <label for="ativo"><strong>Ativa</strong></label>
            </div>
        </div>
    </div>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-lock"></i> Acesso ao sistema</div>

        <?php if ($id > 0): ?>
            <p style="font-size:0.85rem;color:#666;margin-bottom:10px;">Deixe em branco para manter a senha atual.</p>
        <?php else: ?>
            <p style="font-size:0.85rem;color:#666;margin-bottom:10px;">A recepcionista usará o e-mail e essa senha para fazer login.</p>
        <?php endif; ?>

        <div style="display:flex;gap:15px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label for="senha"><strong>Senha <?php echo $id === 0 ? '*' : '(nova)'; ?></strong></label>
                <input type="password" id="senha" name="senha" <?php echo $id === 0 ? 'required' : ''; ?>
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
            <div style="flex:1;min-width:200px;">
                <label for="confirmacao_senha"><strong>Confirmar senha <?php echo $id === 0 ? '*' : ''; ?></strong></label>
                <input type="password" id="confirmacao_senha" name="confirmacao_senha"
                       <?php echo $id === 0 ? 'required' : ''; ?> autocomplete="new-password"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-action">Salvar</button>
    <a href="?acao=recepcionistas" class="btn-action secondary">Voltar</a>
</form>
