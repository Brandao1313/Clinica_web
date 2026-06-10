<?php
// ====================================================
// ARQUIVO: backend/views/relatorios.php
// Descrição: Relatórios de produtividade e financeiro -
//            incluído por painel_admin.php
//
// Todos os cálculos são feitos via SQL (consultas agregadas).
// ====================================================

$hoje = date('Y-m-d');
$mes_atual = date('Y-m');
$ano_atual = date('Y');

// ---------- Cards de produtividade ----------
function contar_agendamentos($conexao_db, $tipo, $periodo_sql) {
    $sql = "SELECT COUNT(*) as total FROM agendamentos
            WHERE tipo = ? AND status != 'cancelado' AND $periodo_sql";
    $stmt = $conexao_db->prepare($sql);
    $stmt->bind_param('s', $tipo);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return (int) $total;
}

$consultas_hoje = contar_agendamentos($conexao_db, 'consulta', "date(data_hora) = '$hoje'");
$consultas_mes = contar_agendamentos($conexao_db, 'consulta', "strftime('%Y-%m', data_hora) = '$mes_atual'");
$consultas_ano = contar_agendamentos($conexao_db, 'consulta', "strftime('%Y', data_hora) = '$ano_atual'");

$exames_hoje = contar_agendamentos($conexao_db, 'exame', "date(data_hora) = '$hoje'");
$exames_mes = contar_agendamentos($conexao_db, 'exame', "strftime('%Y-%m', data_hora) = '$mes_atual'");
$exames_ano = contar_agendamentos($conexao_db, 'exame', "strftime('%Y', data_hora) = '$ano_atual'");

// ---------- Cards financeiros ----------
function resumo_financeiro($conexao_db, $periodo_sql) {
    $sql = "SELECT COALESCE(SUM(valor_total), 0) as faturamento,
                   COALESCE(SUM(valor_medico), 0) as repasse
            FROM agendamentos
            WHERE status = 'concluído' AND $periodo_sql";
    $stmt = $conexao_db->prepare($sql);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'faturamento' => (float) $linha['faturamento'],
        'repasse' => (float) $linha['repasse'],
        'lucro' => (float) $linha['faturamento'] - (float) $linha['repasse'],
    ];
}

$financeiro_mes = resumo_financeiro($conexao_db, "strftime('%Y-%m', data_realizacao) = '$mes_atual'");
$financeiro_ano = resumo_financeiro($conexao_db, "strftime('%Y', data_realizacao) = '$ano_atual'");
?>

<h3>📊 Produtividade</h3>

<div class="stats">
    <div class="stat-card stat-card-primario">
        <div class="stat-icone">🩺</div>
        <h3>Consultas Hoje</h3>
        <div class="stat-number"><?php echo $consultas_hoje; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone">🩺</div>
        <h3>Consultas no Mês</h3>
        <div class="stat-number"><?php echo $consultas_mes; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone">🩺</div>
        <h3>Consultas no Ano</h3>
        <div class="stat-number"><?php echo $consultas_ano; ?></div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icone">🧬</div>
        <h3>Exames Hoje</h3>
        <div class="stat-number"><?php echo $exames_hoje; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone">🧬</div>
        <h3>Exames no Mês</h3>
        <div class="stat-number"><?php echo $exames_mes; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone">🧬</div>
        <h3>Exames no Ano</h3>
        <div class="stat-number"><?php echo $exames_ano; ?></div>
    </div>
</div>

<h3 style="margin-top: 30px;">💰 Financeiro - Mês Atual</h3>
<div class="stats">
    <div class="stat-card stat-card-success">
        <div class="stat-icone">💵</div>
        <h3>Faturamento Bruto</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_mes['faturamento']); ?></div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icone">🩺</div>
        <h3>Repassado aos Médicos</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_mes['repasse']); ?></div>
    </div>
    <div class="stat-card stat-card-primario">
        <div class="stat-icone">📈</div>
        <h3>Lucro Líquido</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_mes['lucro']); ?></div>
    </div>
</div>

<h3 style="margin-top: 30px;">💰 Financeiro - Ano Atual</h3>
<div class="stats">
    <div class="stat-card stat-card-success">
        <div class="stat-icone">💵</div>
        <h3>Faturamento Bruto</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_ano['faturamento']); ?></div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icone">🩺</div>
        <h3>Repassado aos Médicos</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_ano['repasse']); ?></div>
    </div>
    <div class="stat-card stat-card-primario">
        <div class="stat-icone">📈</div>
        <h3>Lucro Líquido</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_ano['lucro']); ?></div>
    </div>
</div>

<h3 style="margin-top: 30px;">Atendimentos por Médico (Mês Atual)</h3>

<?php
    $stmt = $conexao_db->prepare(
        "SELECT m.id, m.nome, m.crm,
                COUNT(a.id) as total_atendimentos,
                COALESCE(SUM(a.valor_total), 0) as faturamento,
                COALESCE(SUM(a.valor_medico), 0) as repasse
         FROM medicos m
         LEFT JOIN agendamentos a ON a.id_medico = m.id
            AND a.status = 'concluído'
            AND strftime('%Y-%m', a.data_realizacao) = ?
         GROUP BY m.id, m.nome, m.crm
         ORDER BY total_atendimentos DESC, m.nome"
    );
    $stmt->bind_param('s', $mes_atual);
    $stmt->execute();
    $relatorio_medicos = $stmt->get_result()->fetch_all();
    $stmt->close();

    $maximo_atendimentos = 1;
    foreach ($relatorio_medicos as $r) {
        $maximo_atendimentos = max($maximo_atendimentos, (int) $r['total_atendimentos']);
    }
?>

<div class="grafico-barras">
    <?php foreach ($relatorio_medicos as $r): ?>
        <?php $altura = $maximo_atendimentos > 0 ? round(((int) $r['total_atendimentos'] / $maximo_atendimentos) * 100) : 0; ?>
        <div class="grafico-barra-item">
            <div class="grafico-barra-coluna" style="height: <?php echo max(4, $altura); ?>%;" title="<?php echo (int) $r['total_atendimentos']; ?> atendimento(s)"></div>
            <div class="grafico-barra-valor"><?php echo (int) $r['total_atendimentos']; ?></div>
            <div class="grafico-barra-label"><?php echo htmlspecialchars($r['nome']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Médico</th>
            <th>CRM</th>
            <th>Atendimentos no Mês</th>
            <th>Faturamento Gerado</th>
            <th>Valor a Repassar</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($relatorio_medicos)): ?>
            <tr><td colspan="5" style="text-align: center;">Nenhum médico cadastrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($relatorio_medicos as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nome']); ?></td>
                <td><?php echo htmlspecialchars($r['crm']); ?></td>
                <td><?php echo (int) $r['total_atendimentos']; ?></td>
                <td><?php echo formatar_valor($r['faturamento']); ?></td>
                <td><?php echo formatar_valor($r['repasse']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;">Faturamento Diário (Últimos 7 dias)</h3>

<?php
    $stmt = $conexao_db->prepare(
        "SELECT date(data_realizacao) as dia,
                COUNT(*) as total_atendimentos,
                COALESCE(SUM(valor_total), 0) as faturamento,
                COALESCE(SUM(valor_medico), 0) as repasse
         FROM agendamentos
         WHERE status = 'concluído' AND data_realizacao >= date('now', '-6 days')
         GROUP BY date(data_realizacao)
         ORDER BY dia DESC"
    );
    $stmt->execute();
    $faturamento_diario = $stmt->get_result()->fetch_all();
    $stmt->close();
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Data</th>
            <th>Atendimentos</th>
            <th>Faturamento Bruto</th>
            <th>Repasse aos Médicos</th>
            <th>Lucro Líquido</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($faturamento_diario)): ?>
            <tr><td colspan="5" style="text-align: center;">Nenhum atendimento concluído nos últimos 7 dias.</td></tr>
        <?php endif; ?>
        <?php foreach ($faturamento_diario as $f): ?>
            <tr>
                <td><?php echo formatar_data($f['dia']); ?></td>
                <td><?php echo (int) $f['total_atendimentos']; ?></td>
                <td><?php echo formatar_valor($f['faturamento']); ?></td>
                <td><?php echo formatar_valor($f['repasse']); ?></td>
                <td><?php echo formatar_valor($f['faturamento'] - $f['repasse']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
