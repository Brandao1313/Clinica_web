<?php
// ====================================================
// ARQUIVO: backend/controllers/exportar_controller.php
// Descrição: Exporta dados do usuário autenticado em CSV
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_login();

$tipo       = sanitizar_input($_GET['tipo'] ?? '');
$id_cliente = $_SESSION['id_cliente'];

if ($tipo === 'agendamentos') {
    $stmt = Conexao::getInstance()->getConexao()->prepare(
        "SELECT a.data_hora,
                a.tipo,
                COALESCE(esp.nome, ex.nome, '-') as item,
                COALESCE(m.nome, '-')            as medico,
                a.status,
                COALESCE(a.valor_total, 0)       as valor,
                a.notas
         FROM agendamentos a
         LEFT JOIN especialidades esp ON a.id_especialidade = esp.id
         LEFT JOIN exames         ex  ON a.id_exame         = ex.id
         LEFT JOIN medicos        m   ON a.id_medico        = m.id
         WHERE a.id_cliente = ?
         ORDER BY a.data_hora DESC"
    );
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $linhas = $stmt->get_result()->fetch_all();
    $stmt->close();

    $nome_arquivo = 'agendamentos_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $saida = fopen('php://output', 'w');

    // BOM UTF-8 para compatibilidade com Excel
    fwrite($saida, "\xEF\xBB\xBF");

    fputcsv($saida, ['Data/Hora', 'Tipo', 'Especialidade/Exame', 'Médico', 'Status', 'Valor (R$)', 'Observações'], ';');

    foreach ($linhas as $l) {
        fputcsv($saida, [
            formatar_data_hora($l['data_hora']),
            get_tipo_agendamento($l['tipo']),
            $l['item'],
            $l['medico'],
            get_status_agendamento($l['status']),
            number_format((float) $l['valor'], 2, ',', '.'),
            $l['notas'] ?? '',
        ], ';');
    }

    fclose($saida);
    exit;
}

// Tipo desconhecido
header('HTTP/1.1 400 Bad Request');
exit('Tipo de exportação inválido');
?>
