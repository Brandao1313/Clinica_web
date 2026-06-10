<?php
// ====================================================
// ARQUIVO: backend/controllers/horarios_disponiveis.php
// Descrição: Endpoint AJAX (JSON) que retorna os horários
//            disponíveis de um médico em uma data específica
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas usuários autenticados podem consultar horários
if (!is_autenticado()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

$id_medico = intval($_GET['id_medico'] ?? 0);
$data = sanitizar_input($_GET['data'] ?? '');

if ($id_medico <= 0 || !validar_data($data)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

$conexao_db = Conexao::getInstance()->getConexao();

// Verificar se o médico existe e está ativo
$stmt = $conexao_db->prepare('SELECT id FROM medicos WHERE id = ? AND ativo = 1');
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$medico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$medico) {
    echo json_encode(['horarios' => []]);
    exit;
}

// Não permitir consultar datas passadas
$hoje = date('Y-m-d');
if ($data < $hoje) {
    echo json_encode(['horarios' => []]);
    exit;
}

// dia_semana: 0 (domingo) a 6 (sábado), igual ao formato do banco
$dia_semana = (int) date('w', strtotime($data));

// Buscar janelas de atendimento do médico para este dia da semana
$stmt = $conexao_db->prepare(
    'SELECT hora_inicio, hora_fim, intervalo_minutos
     FROM horarios_atendimento
     WHERE id_medico = ? AND dia_semana = ? AND ativo = 1'
);
$stmt->bind_param('ii', $id_medico, $dia_semana);
$stmt->execute();
$janelas = $stmt->get_result()->fetch_all();
$stmt->close();

// Gerar todos os slots possíveis a partir das janelas cadastradas
$todos_slots = [];
foreach ($janelas as $janela) {
    $slots = gerar_slots_horario($janela['hora_inicio'], $janela['hora_fim'], $janela['intervalo_minutos']);
    foreach ($slots as $slot) {
        $todos_slots[$slot] = true;
    }
}

// Buscar horários já ocupados (agendamentos pendentes/confirmados) nesse dia
$stmt = $conexao_db->prepare(
    "SELECT data_hora FROM agendamentos
     WHERE id_medico = ? AND status IN ('pendente', 'confirmado')
       AND date(data_hora) = ?"
);
$stmt->bind_param('is', $id_medico, $data);
$stmt->execute();
$ocupados_resultado = $stmt->get_result()->fetch_all();
$stmt->close();

$ocupados = [];
foreach ($ocupados_resultado as $linha) {
    $ocupados[substr($linha['data_hora'], 11, 5)] = true;
}

// Se a data consultada for hoje, remover horários que já passaram
$agora = new DateTime();
$eh_hoje = ($data === $hoje);

$horarios_disponiveis = [];
foreach (array_keys($todos_slots) as $slot) {
    if (isset($ocupados[$slot])) {
        continue;
    }

    if ($eh_hoje) {
        $slot_datetime = DateTime::createFromFormat('Y-m-d H:i', $data . ' ' . $slot);
        if ($slot_datetime <= $agora) {
            continue;
        }
    }

    $horarios_disponiveis[] = $slot;
}

sort($horarios_disponiveis);

echo json_encode(['horarios' => $horarios_disponiveis]);

?>
