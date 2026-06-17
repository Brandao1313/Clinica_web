<?php
// ====================================================
// ARQUIVO: backend/views/especialidade_form.php
// Descrição: Formulário de cadastro/edição de
//            especialidade - incluído por painel_admin.php
// ====================================================

$id_especialidade = intval($_GET['id'] ?? 0);
$especialidade = null;

if ($id_especialidade > 0) {
    $stmt = $conexao_db->prepare('SELECT * FROM especialidades WHERE id = ?');
    $stmt->bind_param('i', $id_especialidade);
    $stmt->execute();
    $especialidade = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$especialidade) {
        echo '<div class="alert alert-error">Especialidade não encontrada.</div>';
        return;
    }
}

// Reaproveitar dados submetidos em caso de erro de validação
$dados = $_SESSION['dados_especialidade'] ?? null;
unset($_SESSION['dados_especialidade']);

$valores = [
    'nome' => $dados['nome'] ?? $especialidade['nome'] ?? '',
    'descricao' => $dados['descricao'] ?? $especialidade['descricao'] ?? '',
    'ativo' => $dados['ativo'] ?? ($especialidade['ativo'] ?? 1),
];
?>

<h3><?php echo $id_especialidade > 0 ? 'Editar Especialidade' : 'Nova Especialidade'; ?></h3>

<?php if (!empty($_SESSION['erros_especialidade'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_especialidade'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_especialidade']); ?>
<?php endif; ?>

<form method="POST" action="../controllers/especialidade_controller.php" style="max-width: 600px;">
    <input type="hidden" name="acao" value="salvar_especialidade">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
    <?php if ($id_especialidade > 0): ?>
        <input type="hidden" name="id_especialidade" value="<?php echo $id_especialidade; ?>">
    <?php endif; ?>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-hospital"></i> Dados da especialidade</div>

        <div style="margin-bottom: 15px;">
            <label for="nome"><strong>Nome *</strong></label>
            <input type="text" id="nome" name="nome" required minlength="3" maxlength="100" value="<?php echo htmlspecialchars($valores['nome']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="descricao"><strong>Descrição</strong></label>
            <textarea id="descricao" name="descricao" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"><?php echo htmlspecialchars($valores['descricao']); ?></textarea>
        </div>

        <div style="margin-bottom: 0;">
            <label>
                <input type="checkbox" name="ativo" value="1" <?php echo $valores['ativo'] ? 'checked' : ''; ?>>
                <strong>Especialidade ativa</strong>
            </label>
        </div>
    </div>

    <button type="submit" class="btn-action">Salvar</button>
    <a href="?acao=especialidades" class="btn-action secondary">Voltar</a>
</form>
