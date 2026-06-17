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

function contar_concluidos($conexao_db, $periodo_sql) {
    $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE status = 'concluído' AND $periodo_sql";
    $stmt = $conexao_db->prepare($sql);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return (int) $total;
}

$realizados_mes = contar_concluidos($conexao_db, "strftime('%Y-%m', data_realizacao) = '$mes_atual'");
$realizados_ano = contar_concluidos($conexao_db, "strftime('%Y', data_realizacao) = '$ano_atual'");

// ---------- Filtro de período (mês de referência) para o relatório por médico ----------
$mes_ref = sanitizar_input($_GET['mes_ref'] ?? $mes_atual);
if (!preg_match('/^\d{4}-\d{2}$/', $mes_ref)) {
    $mes_ref = $mes_atual;
}

// ---------- Faturamento mensal (últimos 6 meses) para o gráfico ----------
$stmt = $conexao_db->prepare(
    "SELECT strftime('%Y-%m', data_realizacao) as mes,
            COALESCE(SUM(valor_total), 0) as faturamento,
            COALESCE(SUM(valor_medico), 0) as repasse
     FROM agendamentos
     WHERE status = 'concluído' AND data_realizacao >= date('now', 'start of month', '-5 months')
     GROUP BY strftime('%Y-%m', data_realizacao)
     ORDER BY mes ASC"
);
$stmt->execute();
$faturamento_por_mes_raw = $stmt->get_result()->fetch_all();
$stmt->close();

$faturamento_por_mes = [];
foreach ($faturamento_por_mes_raw as $linha) {
    $faturamento_por_mes[$linha['mes']] = $linha;
}

$meses_grafico = [];
for ($i = 5; $i >= 0; $i--) {
    $chave = date('Y-m', strtotime("-$i months"));
    $meses_grafico[] = [
        'mes' => $chave,
        'faturamento' => isset($faturamento_por_mes[$chave]) ? (float) $faturamento_por_mes[$chave]['faturamento'] : 0.0,
        'repasse' => isset($faturamento_por_mes[$chave]) ? (float) $faturamento_por_mes[$chave]['repasse'] : 0.0,
    ];
}

$maximo_faturamento_mensal = 1;
foreach ($meses_grafico as $m) {
    $maximo_faturamento_mensal = max($maximo_faturamento_mensal, $m['faturamento']);
}

function formatar_mes_ref($mes_ref) {
    $meses_nomes = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
    [$ano, $mes] = explode('-', $mes_ref);
    return ($meses_nomes[$mes] ?? $mes) . '/' . $ano;
}
?>

<h3><i class="fa-solid fa-chart-bar"></i> Produtividade</h3>

<div class="stats">
    <div class="stat-card stat-card-primario">
        <div class="stat-icone"><i class="fa-solid fa-stethoscope"></i></div>
        <h3>Consultas Hoje</h3>
        <div class="stat-number"><?php echo $consultas_hoje; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-stethoscope"></i></div>
        <h3>Consultas no Mês</h3>
        <div class="stat-number"><?php echo $consultas_mes; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-stethoscope"></i></div>
        <h3>Consultas no Ano</h3>
        <div class="stat-number"><?php echo $consultas_ano; ?></div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icone"><i class="fa-solid fa-dna"></i></div>
        <h3>Exames Hoje</h3>
        <div class="stat-number"><?php echo $exames_hoje; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-dna"></i></div>
        <h3>Exames no Mês</h3>
        <div class="stat-number"><?php echo $exames_mes; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-dna"></i></div>
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
        <div class="stat-icone"><i class="fa-solid fa-stethoscope"></i></div>
        <h3>Repassado aos Médicos</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_mes['repasse']); ?></div>
    </div>
    <div class="stat-card stat-card-primario">
        <div class="stat-icone"><i class="fa-solid fa-chart-line"></i></div>
        <h3>Lucro Líquido</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_mes['lucro']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-circle-check"></i></div>
        <h3>Consultas/Exames Realizados</h3>
        <div class="stat-number"><?php echo $realizados_mes; ?></div>
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
        <div class="stat-icone"><i class="fa-solid fa-stethoscope"></i></div>
        <h3>Repassado aos Médicos</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_ano['repasse']); ?></div>
    </div>
    <div class="stat-card stat-card-primario">
        <div class="stat-icone"><i class="fa-solid fa-chart-line"></i></div>
        <h3>Lucro Líquido</h3>
        <div class="stat-number"><?php echo formatar_valor($financeiro_ano['lucro']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icone"><i class="fa-solid fa-circle-check"></i></div>
        <h3>Consultas/Exames Realizados</h3>
        <div class="stat-number"><?php echo $realizados_ano; ?></div>
    </div>
</div>

<h3 style="margin-top: 30px;">Faturamento Mensal (Últimos 6 meses)</h3>

<div class="grafico-barras">
    <?php foreach ($meses_grafico as $m): ?>
        <?php $altura = $maximo_faturamento_mensal > 0 ? round(($m['faturamento'] / $maximo_faturamento_mensal) * 100) : 0; ?>
        <div class="grafico-barra-item">
            <div class="grafico-barra-coluna" style="height: <?php echo max(4, $altura); ?>%;" title="<?php echo formatar_valor($m['faturamento']); ?>"></div>
            <div class="grafico-barra-valor"><?php echo formatar_valor($m['faturamento']); ?></div>
            <div class="grafico-barra-label"><?php echo formatar_mes_ref($m['mes']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<h3 style="margin-top: 30px;">Atendimentos por Médico (<?php echo formatar_mes_ref($mes_ref); ?>)</h3>

<form method="GET" style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
    <input type="hidden" name="acao" value="relatorios">
    <label for="mes_ref"><strong>Mês de referência:</strong></label>
    <input type="month" id="mes_ref" name="mes_ref" value="<?php echo htmlspecialchars($mes_ref); ?>" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
    <button type="submit" class="btn-action">Filtrar</button>
    <?php if ($mes_ref !== $mes_atual): ?>
        <a href="?acao=relatorios" class="btn-action secondary">Mês atual</a>
    <?php endif; ?>
</form>

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
    $stmt->bind_param('s', $mes_ref);
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
