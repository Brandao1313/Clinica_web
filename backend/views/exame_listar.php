<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/exame_listar.php
// Descrição: Listagem de exames cadastrados
//            - incluído por painel_admin.php
// ====================================================

$flash_exame = get_flash_message('exame');
?>

<?php if ($flash_exame): ?>
    <div class="alert <?php echo $flash_exame['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_exame['mensagem']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['erros_exame'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_exame'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_exame']); ?>
<?php endif; ?>

<h3>Exames</h3>

<div style="display: flex; justify-content: flex-end; margin: 15px 0;">
    <a href="?acao=exame_form" class="btn-action">+ Novo Exame</a>
</div>

<?php
    $stmt = $conexao_db->prepare('SELECT * FROM exames ORDER BY nome');
    $stmt->execute();
    $exames = $stmt->get_result();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Preço</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($exames->num_rows === 0): ?>
            <tr><td colspan="5" style="text-align: center;">Nenhum exame cadastrado.</td></tr>
        <?php endif; ?>
        <?php while ($exame = $exames->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($exame['nome']); ?></td>
                <td><?php echo truncar_texto(htmlspecialchars($exame['descricao'] ?? ''), 50); ?></td>
                <td><?php echo formatar_valor($exame['preco']); ?></td>
                <td>
                    <span class="badge <?php echo $exame['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $exame['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </td>
                <td style="white-space: nowrap;">
                    <a href="?acao=exame_form&id=<?php echo $exame['id']; ?>" class="action-btn action-edit">Editar</a>

                    <form method="POST" action="../controllers/exame_controller.php" style="display: inline;">
                        <input type="hidden" name="acao" value="alternar_status_exame">
                        <input type="hidden" name="id_exame" value="<?php echo $exame['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-edit"><?php echo $exame['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                    </form>

                    <form method="POST" action="../controllers/exame_controller.php" style="display: inline;" data-confirm="Deseja realmente excluir este exame? Esta ação não pode ser desfeita." data-confirm-titulo="Excluir exame">
                        <input type="hidden" name="acao" value="excluir_exame">
                        <input type="hidden" name="id_exame" value="<?php echo $exame['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-delete">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
