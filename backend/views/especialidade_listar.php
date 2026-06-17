<?php
// ====================================================
// ARQUIVO: backend/views/especialidade_listar.php
// Descrição: Listagem de especialidades cadastradas
//            - incluído por painel_admin.php
// ====================================================

$flash_especialidade = get_flash_message('especialidade');
?>

<?php if ($flash_especialidade): ?>
    <div class="alert <?php echo $flash_especialidade['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_especialidade['mensagem']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['erros_especialidade'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_especialidade'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_especialidade']); ?>
<?php endif; ?>

<h3>Especialidades</h3>

<div style="display: flex; justify-content: flex-end; margin: 15px 0;">
    <a href="?acao=especialidade_form" class="btn-action">+ Nova Especialidade</a>
</div>

<?php
    $stmt = $conexao_db->prepare('SELECT * FROM especialidades ORDER BY nome');
    $stmt->execute();
    $especialidades = $stmt->get_result();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($especialidades->num_rows === 0): ?>
            <tr><td colspan="4" style="text-align: center;">Nenhuma especialidade cadastrada.</td></tr>
        <?php endif; ?>
        <?php while ($esp = $especialidades->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($esp['nome']); ?></td>
                <td><?php echo truncar_texto(htmlspecialchars($esp['descricao'] ?? ''), 60); ?></td>
                <td>
                    <span class="badge <?php echo $esp['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $esp['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </td>
                <td style="white-space: nowrap;">
                    <a href="?acao=especialidade_form&id=<?php echo $esp['id']; ?>" class="action-btn action-edit">Editar</a>

                    <form method="POST" action="../controllers/especialidade_controller.php" style="display: inline;">
                        <input type="hidden" name="acao" value="alternar_status_especialidade">
                        <input type="hidden" name="id_especialidade" value="<?php echo $esp['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-edit"><?php echo $esp['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                    </form>

                    <form method="POST" action="../controllers/especialidade_controller.php" style="display: inline;" data-confirm="Deseja realmente excluir esta especialidade? Esta ação não pode ser desfeita." data-confirm-titulo="Excluir especialidade">
                        <input type="hidden" name="acao" value="excluir_especialidade">
                        <input type="hidden" name="id_especialidade" value="<?php echo $esp['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-delete">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
