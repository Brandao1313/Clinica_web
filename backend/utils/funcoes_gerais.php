<?php
// ====================================================
// ARQUIVO: backend/utils/funcoes_gerais.php
// Descrição: Funções gerais reutilizáveis
// ====================================================

/**
 * Formatar CPF para exibição (000.000.000-00)
 * @param string $cpf
 * @return string
 */
function formatar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Formatar telefone para exibição ((11) 99999-9999)
 * @param string $telefone
 * @return string
 */
function formatar_telefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}

/**
 * Formatar data do banco (YYYY-MM-DD) para exibição (DD/MM/YYYY)
 * @param string $data
 * @return string
 */
function formatar_data($data) {
    if (empty($data)) return '';
    try {
        $d = DateTime::createFromFormat('Y-m-d', $data);
        return $d->format('d/m/Y');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Formatar data e hora para exibição
 * @param string $data_hora
 * @return string
 */
function formatar_data_hora($data_hora) {
    if (empty($data_hora)) return '';
    try {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $data_hora);
        return $d->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Calcular idade a partir de data de nascimento
 * @param string $data_nascimento (YYYY-MM-DD)
 * @return int
 */
function calcular_idade($data_nascimento) {
    try {
        $data = new DateTime($data_nascimento);
        $hoje = new DateTime();
        return $hoje->diff($data)->y;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Obter status de agendamento em português
 * @param string $status
 * @return string
 */
function get_status_agendamento($status) {
    $status_map = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'cancelado' => 'Cancelado',
        'concluído' => 'Concluído'
    ];
    return $status_map[$status] ?? $status;
}

/**
 * Obter cor/classe CSS para status
 * @param string $status
 * @return string
 */
function get_classe_status($status) {
    $classe_map = [
        'pendente' => 'badge-warning',
        'confirmado' => 'badge-success',
        'cancelado' => 'badge-danger',
        'concluído' => 'badge-info'
    ];
    return $classe_map[$status] ?? 'badge-secondary';
}

/**
 * Obter tipo de agendamento em português
 * @param string $tipo
 * @return string
 */
function get_tipo_agendamento($tipo) {
    $tipo_map = [
        'consulta' => 'Consulta',
        'exame' => 'Exame'
    ];
    return $tipo_map[$tipo] ?? $tipo;
}

/**
 * Verificar se agendamento pode ser cancelado (apenas pendente e confirmado)
 * @param string $status
 * @return bool
 */
function pode_cancelar_agendamento($status) {
    return in_array($status, ['pendente', 'confirmado']);
}

/**
 * Verificar se data/hora de agendamento é no futuro
 * @param string $data_hora
 * @return bool
 */
function is_agendamento_futuro($data_hora) {
    try {
        $data = new DateTime($data_hora);
        $agora = new DateTime();
        return $data > $agora;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gerar hora de expiração do token (24 horas a partir de agora)
 * @return string (formato: YYYY-MM-DD HH:MM:SS)
 */
function gerar_expiracao_token() {
    $data = new DateTime();
    $data->modify('+24 hours');
    return $data->format('Y-m-d H:i:s');
}

/**
 * Verificar se token expirou
 * @param string $data_expiracao
 * @return bool
 */
function token_expirou($data_expiracao) {
    try {
        $expiracao = new DateTime($data_expiracao);
        $agora = new DateTime();
        return $agora > $expiracao;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * Formatar valor em reais
 * @param float $valor
 * @return string
 */
function formatar_valor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Validar data futuro (para agendamentos)
 * @param string $data_hora (YYYY-MM-DD HH:MM)
 * @return bool
 */
function validar_data_agendamento($data_hora) {
    try {
        $agendamento = new DateTime($data_hora);
        $agora = new DateTime();
        $agora->modify('+1 hour'); // Não permitir agendamento menos de 1 hora no futuro

        return $agendamento > $agora;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Log de ações (para auditoria futura)
 * @param string $acao
 * @param int $id_usuario
 * @param string $detalhes
 * @return void
 */
function log_acao($acao, $id_usuario, $detalhes = '') {
    // Implementação básica: apenas registra em arquivo
    // Em produção, seria melhor usar banco de dados
    $data = date('Y-m-d H:i:s');
    $log = "[$data] ACAO: $acao | USER_ID: $id_usuario | DETALHES: $detalhes\n";
    $arquivo_log = __DIR__ . '/../../logs/atividades.log';

    // Criar pasta de logs se não existir
    if (!is_dir(dirname($arquivo_log))) {
        mkdir(dirname($arquivo_log), 0755, true);
    }

    file_put_contents($arquivo_log, $log, FILE_APPEND | LOCK_EX);
}

/**
 * Truncar texto com "..."
 * @param string $texto
 * @param int $limite
 * @return string
 */
function truncar_texto($texto, $limite = 100) {
    if (strlen($texto) > $limite) {
        return substr($texto, 0, $limite) . '...';
    }
    return $texto;
}

?>
