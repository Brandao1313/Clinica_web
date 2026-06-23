<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/recepcionista_listar.php
// Descrição: Listagem de recepcionistas
//            - incluído por painel_admin.php
// ====================================================

$flash_recep = get_flash_message('recepcionista');
?>

<?php if ($flash_recep): ?>
    <div class="alert <?php echo $flash_recep['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_recep['mensagem']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['erros_recepcionista'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_recepcionista'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_recepcionista']); ?>
<?php endif; ?>

<h3><i class="fa-solid fa-id-badge"></i> Recepcionistas</h3>

<div style="display:flex; justify-content:flex-end; margin: 15px 0;">
    <a href="?acao=recepcionista_form" class="btn-action">+ Nova Recepcionista</a>
</div>

<?php
    $stmt = $conexao_db->prepare(
        'SELECT id, nome, email, cpf, telefone, ativo, data_cadastro
         FROM clientes WHERE tipo = "recepcionista" ORDER BY nome'
    );
    $stmt->execute();
    $lista = $stmt->get_result();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>CPF</th>
            <th>Telefone</th>
            <th>Cadastro</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($lista->num_rows === 0): ?>
            <tr><td colspan="7" style="text-align:center;">Nenhuma recepcionista cadastrada.</td></tr>
        <?php endif; ?>
        <?php while ($r = $lista->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nome']); ?></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td><?php echo formatar_cpf($r['cpf']); ?></td>
                <td><?php echo !empty($r['telefone']) ? formatar_telefone($r['telefone']) : '-'; ?></td>
                <td><?php echo formatar_data($r['data_cadastro']); ?></td>
                <td>
                    <span class="badge <?php echo $r['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $r['ativo'] ? 'Ativa' : 'Inativa'; ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <a href="?acao=recepcionista_form&id=<?php echo $r['id']; ?>" class="action-btn action-edit">Editar</a>

                    <form method="POST" action="../controllers/admin_recepcionista_controller.php" style="display:inline;">
                        <input type="hidden" name="acao" value="alternar_status_recepcionista">
                        <input type="hidden" name="id_recepcionista" value="<?php echo $r['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-edit">
                            <?php echo $r['ativo'] ? 'Desativar' : 'Ativar'; ?>
                        </button>
                    </form>

                    <form method="POST" action="../controllers/admin_recepcionista_controller.php" style="display:inline;"
                          data-confirm="Deseja excluir esta recepcionista? Esta ação não pode ser desfeita."
                          data-confirm-titulo="Excluir recepcionista">
                        <input type="hidden" name="acao" value="excluir_recepcionista">
                        <input type="hidden" name="id_recepcionista" value="<?php echo $r['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-delete">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
