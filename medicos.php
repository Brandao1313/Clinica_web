<?php
// ====================================================
// ARQUIVO: medicos.php
// Descrição: Página pública - listagem de médicos ativos
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';
require_once __DIR__ . '/backend/utils/funcoes_gerais.php';

$conexao_db = Conexao::getInstance()->getConexao();

$base_url = '';
$titulo_pagina = 'Nossos Médicos - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/backend/includes/header.php';

$stmt = $conexao_db->prepare(
    "SELECT m.*, e.nome as nome_especialidade
     FROM medicos m
     LEFT JOIN especialidades e ON m.id_especialidade = e.id
     WHERE m.ativo = 1
     ORDER BY e.nome, m.nome"
);
$stmt->execute();
$medicos = $stmt->get_result()->fetch_all();
$stmt->close();

// Carrega horários de todos os médicos ativos de uma vez
$horarios_por_medico = [];
if (!empty($medicos)) {
    $stmt = $conexao_db->prepare(
        "SELECT h.* FROM horarios_atendimento h
         INNER JOIN medicos m ON h.id_medico = m.id
         WHERE m.ativo = 1
         ORDER BY h.dia_semana, h.hora_inicio"
    );
    $stmt->execute();
    $todos_horarios = $stmt->get_result()->fetch_all();
    $stmt->close();

    foreach ($todos_horarios as $h) {
        $horarios_por_medico[(int) $h['id_medico']][] = $h;
    }
}

function formatar_horarios_resumo(array $horarios): string {
    if (empty($horarios)) {
        return 'Horários não cadastrados';
    }

    $partes = [];
    foreach ($horarios as $h) {
        $dia = obter_abrev_dia_semana((int) $h['dia_semana']);
        $partes[$dia][] = formatar_hora($h['hora_inicio']) . ' - ' . formatar_hora($h['hora_fim']);
    }

    $linhas = [];
    foreach ($partes as $dia => $faixas) {
        $linhas[] = '<strong>' . htmlspecialchars($dia) . ':</strong> ' . htmlspecialchars(implode(', ', array_unique($faixas)));
    }

    return implode('<br>', $linhas);
}
?>

    <div class="medicos-container">
        <h2>Nossos Médicos</h2>

        <?php if (empty($medicos)): ?>
            <div class="nenhum">
                <p>Nenhum médico disponível no momento.</p>
            </div>
        <?php else: ?>
            <div class="medicos-grid">
                <?php foreach ($medicos as $medico): ?>
                    <div class="medico-card">
                        <div class="medico-card-cabecalho">
                            <?php if (!empty($medico['foto'])): ?>
                                <img class="medico-foto" src="<?php echo htmlspecialchars($medico['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($medico['nome']); ?>">
                            <?php else: ?>
                                <div class="medico-foto-placeholder"><i class="fa-solid fa-stethoscope"></i></div>
                            <?php endif; ?>
                            <div>
                                <h3>Dr(a). <?php echo htmlspecialchars($medico['nome']); ?></h3>
                                <div class="medico-especialidade"><?php echo htmlspecialchars($medico['nome_especialidade'] ?? '-'); ?></div>
                                <div class="medico-crm">CRM: <?php echo htmlspecialchars($medico['crm']); ?></div>
                            </div>
                        </div>

                        <p class="medico-bio"><?php echo htmlspecialchars(truncar_texto((string) $medico['bio'], 140)); ?></p>

                        <div class="medico-horarios">
                            <?php echo formatar_horarios_resumo($horarios_por_medico[(int) $medico['id']] ?? []); ?>
                        </div>

                        <div class="card-meta">
                            <span class="preco">A partir de <?php echo formatar_valor($medico['valor_consulta']); ?></span>
                        </div>

                        <a href="medico_detalhe.php?id=<?php echo (int) $medico['id']; ?>" class="btn-agendar">Ver Perfil</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/backend/includes/footer.php'; ?>
