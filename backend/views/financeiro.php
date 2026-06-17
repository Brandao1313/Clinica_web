<?php
// ====================================================
// ARQUIVO: backend/views/financeiro.php
// Descrição: Painel financeiro - geração de repasses aos
//            médicos - incluído por painel_admin.php
// ====================================================

$flash_financeiro = get_flash_message('financeiro');
?>

<?php if ($flash_financeiro): ?>
    <div class="alert <?php echo $flash_financeiro['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_financeiro['mensagem']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['erros_financeiro'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_financeiro'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_financeiro']); ?>
<?php endif; ?>

<h3><i class="fa-solid fa-credit-card"></i> Repasses aos Médicos</h3>

<?php
    // Resumo: valor a receber por médico (atendimentos concluídos e ainda não repassados)
    $stmt = $conexao_db->prepare(
        "SELECT m.id, m.nome, m.crm,
                COUNT(a.id) as total_atendimentos,
                COALESCE(SUM(a.valor_total), 0) as total_recebido,
                COALESCE(SUM(a.valor_medico), 0) as valor_a_receber
         FROM medicos m
         LEFT JOIN agendamentos a ON a.id_medico = m.id
            AND a.status = 'concluído'
            AND a.status_pagamento IN ('pendente', 'pago_clinica')
         GROUP BY m.id, m.nome, m.crm
         ORDER BY m.nome"
    );
    $stmt->execute();
    $resumo_medicos = $stmt->get_result()->fetch_all();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Médico</th>
            <th>CRM</th>
            <th>Atendimentos pendentes de repasse</th>
            <th>Valor recebido (clínica)</th>
            <th>Valor a repassar</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($resumo_medicos)): ?>
            <tr><td colspan="5" style="text-align: center;">Nenhum médico cadastrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($resumo_medicos as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nome']); ?></td>
                <td><?php echo htmlspecialchars($r['crm']); ?></td>
                <td><?php echo (int) $r['total_atendimentos']; ?></td>
                <td><?php echo formatar_valor($r['total_recebido']); ?></td>
                <td><strong><?php echo formatar_valor($r['valor_a_receber']); ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;">Gerar Pagamento (Repasse)</h3>
<p style="color: var(--cor-texto-claro); margin-bottom: 15px;">
    Selecione o médico e o período de competência. O sistema irá somar todos os
    atendimentos concluídos e ainda não repassados dentro do período, registrar o
    pagamento e marcar esses atendimentos como "Repassado ao médico".
</p>

<form method="POST" action="../controllers/financeiro_controller.php" style="max-width: 600px;">
    <input type="hidden" name="acao" value="gerar_pagamento">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">

    <div class="form-grupo">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 220px;">
                <label for="id_medico_pagamento"><strong>Médico *</strong></label>
                <select id="id_medico_pagamento" name="id_medico" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($resumo_medicos as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['nome']); ?> (<?php echo htmlspecialchars($r['crm']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 160px;">
                <label for="periodo_inicio"><strong>Período - início *</strong></label>
                <input type="date" id="periodo_inicio" name="periodo_inicio" required value="<?php echo date('Y-m-01'); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="flex: 1; min-width: 160px;">
                <label for="periodo_fim"><strong>Período - fim *</strong></label>
                <input type="date" id="periodo_fim" name="periodo_fim" required value="<?php echo date('Y-m-t'); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-action" data-confirm="Confirma a geração do pagamento para o médico e período selecionados?" data-confirm-titulo="Gerar pagamento">Gerar Pagamento</button>
</form>

<h3 style="margin-top: 30px;">Extrato de Pagamentos Realizados</h3>

<?php
    $stmt = $conexao_db->prepare(
        'SELECT p.*, m.nome as nome_medico, m.crm
         FROM pagamentos_medicos p
         LEFT JOIN medicos m ON p.id_medico = m.id
         ORDER BY p.data_pagamento DESC, p.id DESC'
    );
    $stmt->execute();
    $pagamentos = $stmt->get_result();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Médico</th>
            <th>Período</th>
            <th>Atendimentos</th>
            <th>Valor Total Recebido</th>
            <th>Valor Repassado</th>
            <th>Data Pagamento</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($pagamentos->num_rows === 0): ?>
            <tr><td colspan="7" style="text-align: center;">Nenhum pagamento registrado ainda.</td></tr>
        <?php endif; ?>
        <?php while ($p = $pagamentos->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['nome_medico'] ?? '-'); ?></td>
                <td><?php echo formatar_data($p['periodo_inicio']); ?> a <?php echo formatar_data($p['periodo_fim']); ?></td>
                <td><?php echo (int) $p['total_consultas']; ?></td>
                <td><?php echo formatar_valor($p['valor_total_recebido']); ?></td>
                <td><?php echo formatar_valor($p['valor_repassado']); ?></td>
                <td><?php echo $p['data_pagamento'] ? formatar_data($p['data_pagamento']) : '-'; ?></td>
                <td>
                    <span class="badge <?php echo $p['status'] === 'pago' ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo $p['status'] === 'pago' ? 'Pago' : 'Pendente'; ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
