<?php
// ====================================================
// ARQUIVO: backend/views/medico_listar.php
// Descrição: Listagem (com busca e paginação) de médicos
//            cadastrados - incluído por painel_admin.php
// ====================================================

$busca = sanitizar_input($_GET['busca'] ?? '');

$flash_medico = get_flash_message('medico');
?>

<?php if ($flash_medico): ?>
    <div class="alert <?php echo $flash_medico['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_medico['mensagem']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['erros_medico'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_medico'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_medico']); ?>
<?php endif; ?>

<h3>Médicos Cadastrados</h3>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin: 15px 0;">
    <form method="GET" style="display: flex; gap: 10px;">
        <input type="hidden" name="acao" value="medicos">
        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por nome, CRM ou especialidade" style="padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-width: 280px;">
        <button type="submit" class="btn-action">Buscar</button>
        <?php if (!empty($busca)): ?>
            <a href="?acao=medicos" class="btn-action secondary">Limpar</a>
        <?php endif; ?>
    </form>
    <a href="?acao=medico_form" class="btn-action">+ Novo Médico</a>
</div>

<?php
    $params = [];
    $tipos = '';
    $where = '';

    if (!empty($busca)) {
        $where = 'WHERE m.nome LIKE ? OR m.crm LIKE ? OR e.nome LIKE ?';
        $termo = '%' . $busca . '%';
        $params = [$termo, $termo, $termo];
        $tipos = 'sss';
    }

    $sql_total = "SELECT COUNT(*) as total FROM medicos m LEFT JOIN especialidades e ON m.id_especialidade = e.id $where";
    $stmt = $conexao_db->prepare($sql_total);
    if (!empty($params)) {
        $stmt->bind_param($tipos, $params[0], $params[1], $params[2]);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $total_paginas = max(1, ceil($total / $itens_por_pagina));

    $sql = "SELECT m.*, e.nome as nome_especialidade
            FROM medicos m
            LEFT JOIN especialidades e ON m.id_especialidade = e.id
            $where
            ORDER BY m.nome
            LIMIT ? OFFSET ?";
    $stmt = $conexao_db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($tipos . 'ii', $params[0], $params[1], $params[2], $itens_por_pagina, $offset);
    } else {
        $stmt->bind_param('ii', $itens_por_pagina, $offset);
    }
    $stmt->execute();
    $medicos = $stmt->get_result();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>CRM</th>
            <th>Especialidade</th>
            <th>Valor Consulta</th>
            <th>% Médico</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($medicos->num_rows === 0): ?>
            <tr><td colspan="7" style="text-align: center;">Nenhum médico encontrado.</td></tr>
        <?php endif; ?>
        <?php while ($medico = $medicos->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($medico['nome']); ?></td>
                <td><?php echo htmlspecialchars($medico['crm']); ?></td>
                <td><?php echo htmlspecialchars($medico['nome_especialidade'] ?? '-'); ?></td>
                <td><?php echo formatar_valor($medico['valor_consulta']); ?></td>
                <td><?php echo formatar_percentual($medico['percentual_medico']); ?></td>
                <td>
                    <span class="badge <?php echo $medico['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $medico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </td>
                <td style="white-space: nowrap;">
                    <a href="?acao=medico_form&id=<?php echo $medico['id']; ?>" class="action-btn action-edit">Editar</a>
                    <a href="?acao=horarios&id=<?php echo $medico['id']; ?>" class="action-btn action-edit">Horários</a>

                    <form method="POST" action="../controllers/medico_controller.php" style="display: inline;">
                        <input type="hidden" name="acao" value="alternar_status_medico">
                        <input type="hidden" name="id_medico" value="<?php echo $medico['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-edit"><?php echo $medico['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                    </form>

                    <form method="POST" action="../controllers/medico_controller.php" style="display: inline;" data-confirm="Deseja realmente excluir este médico? Esta ação não pode ser desfeita." data-confirm-titulo="Excluir médico">
                        <input type="hidden" name="acao" value="excluir_medico">
                        <input type="hidden" name="id_medico" value="<?php echo $medico['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="action-btn action-delete">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($pagina > 1): ?>
        <a href="?acao=medicos&busca=<?php echo urlencode($busca); ?>&pagina=<?php echo $pagina - 1; ?>"><i class="fa-solid fa-arrow-left"></i> Anterior</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <span class="<?php echo $i === $pagina ? 'active' : ''; ?>">
            <a href="?acao=medicos&busca=<?php echo urlencode($busca); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
        </span>
    <?php endfor; ?>

    <?php if ($pagina < $total_paginas): ?>
        <a href="?acao=medicos&busca=<?php echo urlencode($busca); ?>&pagina=<?php echo $pagina + 1; ?>">Próxima →</a>
    <?php endif; ?>
</div>
