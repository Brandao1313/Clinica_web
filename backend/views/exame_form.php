<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/exame_form.php
// Descrição: Formulário de cadastro/edição de exame
//            - incluído por painel_admin.php
// ====================================================

$id_exame = intval($_GET['id'] ?? 0);
$exame = null;

if ($id_exame > 0) {
    $stmt = $conexao_db->prepare('SELECT * FROM exames WHERE id = ?');
    $stmt->bind_param('i', $id_exame);
    $stmt->execute();
    $exame = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exame) {
        echo '<div class="alert alert-error">Exame não encontrado.</div>';
        return;
    }
}

// Reaproveitar dados submetidos em caso de erro de validação
$dados = $_SESSION['dados_exame'] ?? null;
unset($_SESSION['dados_exame']);

$valores = [
    'nome' => $dados['nome'] ?? $exame['nome'] ?? '',
    'descricao' => $dados['descricao'] ?? $exame['descricao'] ?? '',
    'preco' => $dados['preco'] ?? $exame['preco'] ?? '',
    'ativo' => $dados['ativo'] ?? ($exame['ativo'] ?? 1),
];
?>

<h3><?php echo $id_exame > 0 ? 'Editar Exame' : 'Novo Exame'; ?></h3>

<?php if (!empty($_SESSION['erros_exame'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_exame'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_exame']); ?>
<?php endif; ?>

<form method="POST" action="../controllers/exame_controller.php" style="max-width: 600px;">
    <input type="hidden" name="acao" value="salvar_exame">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
    <?php if ($id_exame > 0): ?>
        <input type="hidden" name="id_exame" value="<?php echo $id_exame; ?>">
    <?php endif; ?>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-dna"></i> Dados do exame</div>

        <div style="margin-bottom: 15px;">
            <label for="nome"><strong>Nome *</strong></label>
            <input type="text" id="nome" name="nome" required minlength="3" maxlength="100" value="<?php echo htmlspecialchars($valores['nome']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="preco"><strong>Preço (R$) *</strong></label>
            <input type="number" id="preco" name="preco" required min="0.01" step="0.01" value="<?php echo htmlspecialchars($valores['preco']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="descricao"><strong>Descrição</strong></label>
            <textarea id="descricao" name="descricao" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"><?php echo htmlspecialchars($valores['descricao']); ?></textarea>
        </div>

        <div style="margin-bottom: 0;">
            <label>
                <input type="checkbox" name="ativo" value="1" <?php echo $valores['ativo'] ? 'checked' : ''; ?>>
                <strong>Exame ativo</strong>
            </label>
        </div>
    </div>

    <button type="submit" class="btn-action">Salvar</button>
    <a href="?acao=exames" class="btn-action secondary">Voltar</a>
</form>
