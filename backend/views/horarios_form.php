<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/horarios_form.php
// Descrição: Formulário de horários de atendimento de
//            um médico - incluído por painel_admin.php
// ====================================================

$id_medico = intval($_GET['id'] ?? 0);

$stmt = $conexao_db->prepare('SELECT * FROM medicos WHERE id = ?');
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$medico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$medico) {
    echo '<div class="alert alert-error">Médico não encontrado.</div>';
    return;
}

// Carregar horários já cadastrados, agrupados por dia da semana
$stmt = $conexao_db->prepare('SELECT * FROM horarios_atendimento WHERE id_medico = ? ORDER BY dia_semana, hora_inicio');
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$horarios_cadastrados = $stmt->get_result()->fetch_all();
$stmt->close();

$horarios_por_dia = [];
foreach ($horarios_cadastrados as $h) {
    $horarios_por_dia[(int) $h['dia_semana']][] = $h;
}
?>

<h3>Horários de Atendimento - <?php echo htmlspecialchars($medico['nome']); ?></h3>

<?php if (!empty($_SESSION['erros_horarios'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_horarios'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_horarios']); ?>
<?php endif; ?>

<?php $flash_medico = get_flash_message('medico'); ?>
<?php if ($flash_medico): ?>
    <div class="alert <?php echo $flash_medico['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash_medico['mensagem']); ?>
    </div>
<?php endif; ?>

<p style="margin-bottom: 15px; color: var(--cor-texto-claro);">
    Marque os dias em que o médico atende e informe o horário de início, fim e o
    intervalo (em minutos) entre cada consulta. Salvar substitui todos os horários
    cadastrados anteriormente para este médico.
</p>

<form method="POST" action="../controllers/medico_controller.php">
    <input type="hidden" name="acao" value="salvar_horarios">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
    <input type="hidden" name="id_medico" value="<?php echo $id_medico; ?>">

    <table class="admin-table horarios-tabela">
        <thead>
            <tr>
                <th>Dia</th>
                <th>Atende?</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Intervalo (min)</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($dia = 0; $dia <= 6; $dia++):
                $existente = $horarios_por_dia[$dia][0] ?? null;
                $marcado = $existente !== null;
                $hora_inicio = $existente ? formatar_hora($existente['hora_inicio']) : '08:00';
                $hora_fim = $existente ? formatar_hora($existente['hora_fim']) : '18:00';
                $intervalo = $existente ? $existente['intervalo_minutos'] : 30;
            ?>
                <tr>
                    <td><strong><?php echo obter_nome_dia_semana($dia); ?></strong></td>
                    <td>
                        <input type="checkbox" name="dia[]" value="<?php echo $dia; ?>" <?php echo $marcado ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        <input type="time" name="hora_inicio[<?php echo $dia; ?>]" value="<?php echo htmlspecialchars($hora_inicio); ?>" style="padding: 6px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    </td>
                    <td>
                        <input type="time" name="hora_fim[<?php echo $dia; ?>]" value="<?php echo htmlspecialchars($hora_fim); ?>" style="padding: 6px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    </td>
                    <td>
                        <input type="number" name="intervalo[<?php echo $dia; ?>]" value="<?php echo (int) $intervalo; ?>" min="5" max="240" step="5" style="width: 90px; padding: 6px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn-action">Salvar Horários</button>
        <a href="?acao=medicos" class="btn-action secondary">Voltar</a>
    </div>
</form>
