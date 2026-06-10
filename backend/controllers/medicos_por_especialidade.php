<?php
// ====================================================
// ARQUIVO: backend/controllers/medicos_por_especialidade.php
// Descrição: Endpoint AJAX (JSON) que retorna os médicos
//            ativos de uma especialidade
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_autenticado()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

$id_especialidade = intval($_GET['id_especialidade'] ?? 0);

if ($id_especialidade <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

$conexao_db = Conexao::getInstance()->getConexao();

$stmt = $conexao_db->prepare(
    'SELECT id, nome, crm, valor_consulta
     FROM medicos
     WHERE id_especialidade = ? AND ativo = 1
     ORDER BY nome'
);
$stmt->bind_param('i', $id_especialidade);
$stmt->execute();
$medicos = $stmt->get_result()->fetch_all();
$stmt->close();

$lista = [];
foreach ($medicos as $medico) {
    $lista[] = [
        'id' => (int) $medico['id'],
        'nome' => $medico['nome'],
        'crm' => $medico['crm'],
        'valor_consulta' => (float) $medico['valor_consulta'],
        'valor_formatado' => formatar_valor($medico['valor_consulta']),
    ];
}

echo json_encode(['medicos' => $lista]);

?>
