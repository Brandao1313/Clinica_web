<?php
// ====================================================
// ARQUIVO: medico_detalhe.php
// Descrição: Página pública - perfil detalhado do médico
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';
require_once __DIR__ . '/backend/utils/funcoes_gerais.php';

$conexao_db = Conexao::getInstance()->getConexao();

$id_medico = intval($_GET['id'] ?? 0);

$stmt = $conexao_db->prepare(
    "SELECT m.*, e.nome as nome_especialidade
     FROM medicos m
     LEFT JOIN especialidades e ON m.id_especialidade = e.id
     WHERE m.id = ? AND m.ativo = 1"
);
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$medico = $stmt->get_result()->fetch_assoc();
$stmt->close();

$base_url = '';
$titulo_pagina = $medico ? 'Dr(a). ' . $medico['nome'] . ' - Clínica Saúde & Bem-Estar' : 'Médico não encontrado - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/includes/header.php';

if (!$medico) {
?>
    <div class="medico-detalhe-container">
        <div class="alert alert-error">Médico não encontrado.</div>
        <a href="medicos.php" class="btn-agendar">Voltar para Médicos</a>
    </div>
<?php
    require_once __DIR__ . '/includes/footer.php';
    return;
}

$stmt = $conexao_db->prepare('SELECT * FROM horarios_atendimento WHERE id_medico = ? ORDER BY dia_semana, hora_inicio');
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$horarios = $stmt->get_result()->fetch_all();
$stmt->close();

$horarios_por_dia = [];
foreach ($horarios as $h) {
    $horarios_por_dia[(int) $h['dia_semana']][] = $h;
}
?>

    <div class="medico-detalhe-container">
        <div class="medico-detalhe-cabecalho">
            <?php if (!empty($medico['foto'])): ?>
                <img class="medico-foto" src="<?php echo htmlspecialchars($medico['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($medico['nome']); ?>">
            <?php else: ?>
                <div class="medico-foto-placeholder"><i class="fa-solid fa-stethoscope"></i></div>
            <?php endif; ?>
            <div>
                <h2>Dr(a). <?php echo htmlspecialchars($medico['nome']); ?></h2>
                <div class="medico-especialidade"><?php echo htmlspecialchars($medico['nome_especialidade'] ?? '-'); ?></div>
                <div class="medico-crm">CRM: <?php echo htmlspecialchars($medico['crm']); ?></div>
                <div class="card-meta" style="margin-top: 10px;">
                    <span class="preco">Consulta: <?php echo formatar_valor($medico['valor_consulta']); ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($medico['bio'])): ?>
            <div class="medico-detalhe-secao">
                <h3>Sobre</h3>
                <p><?php echo nl2br(htmlspecialchars($medico['bio'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="medico-detalhe-secao">
            <h3>Horários de Atendimento</h3>
            <?php if (empty($horarios_por_dia)): ?>
                <p>Horários não cadastrados no momento.</p>
            <?php else: ?>
                <table class="admin-table horarios-tabela">
                    <thead>
                        <tr>
                            <th>Dia da Semana</th>
                            <th>Horário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($dia = 0; $dia <= 6; $dia++): ?>
                            <?php if (!empty($horarios_por_dia[$dia])): ?>
                                <tr>
                                    <td><?php echo obter_nome_dia_semana($dia); ?></td>
                                    <td>
                                        <?php
                                            $faixas = [];
                                            foreach ($horarios_por_dia[$dia] as $h) {
                                                $faixas[] = formatar_hora($h['hora_inicio']) . ' - ' . formatar_hora($h['hora_fim']);
                                            }
                                            echo htmlspecialchars(implode(', ', $faixas));
                                        ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <?php if (is_autenticado()): ?>
                <a href="backend/views/painel_cliente.php?acao=agendar" class="btn-agendar">Agendar Consulta</a>
            <?php else: ?>
                <a href="cadastro/login.php" class="btn-login">Faça login para agendar</a>
            <?php endif; ?>
            <a href="medicos.php" class="btn-action secondary" style="margin-left: 10px;">Voltar</a>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
