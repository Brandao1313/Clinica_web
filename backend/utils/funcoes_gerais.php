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
        $d = new DateTime($data);
        return $d->format('d/m/Y');
    } catch (Exception $e) {
        return $data;
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
        $d = new DateTime($data_hora);
        return $d->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $data_hora;
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
 * Registrar evento de segurança (tentativas de login, reset de senha, etc.)
 * @param string $evento
 * @param string $detalhes
 * @return void
 */
function log_seguranca($evento, $detalhes = '') {
    $data = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $log = "[$data] EVENTO: $evento | IP: $ip | DETALHES: $detalhes\n";
    $arquivo_log = __DIR__ . '/../../logs/security.log';

    if (!is_dir(dirname($arquivo_log))) {
        mkdir(dirname($arquivo_log), 0755, true);
    }

    file_put_contents($arquivo_log, $log, FILE_APPEND | LOCK_EX);
}

/**
 * Verificar limite de tentativas de verificação telefone+CPF na recuperação de senha
 * Máximo de 3 tentativas por hora por telefone (sessão)
 * @param string $telefone
 * @return bool true se dentro do limite, false se excedeu
 */
function verificar_limite_tentativas_reset($telefone) {
    $agora = time();
    $uma_hora_atras = $agora - 3600;

    $_SESSION['reset_tentativas'] = $_SESSION['reset_tentativas'] ?? [];
    $historico = $_SESSION['reset_tentativas'][$telefone] ?? [];

    // Manter apenas tentativas da última hora
    $historico = array_values(array_filter($historico, function ($timestamp) use ($uma_hora_atras) {
        return $timestamp > $uma_hora_atras;
    }));

    if (count($historico) >= 3) {
        $_SESSION['reset_tentativas'][$telefone] = $historico;
        return false;
    }

    $historico[] = $agora;
    $_SESSION['reset_tentativas'][$telefone] = $historico;
    return true;
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

/**
 * Obter ícone (emoji) representativo de uma especialidade médica
 * @param string $nome
 * @return string
 */
function obter_icone_especialidade($nome) {
    $mapa = [
        'cardiologia'          => 'fa-heart',
        'dermatologia'         => 'fa-pump-soap',
        'oftalmologia'         => 'fa-eye',
        'odontologia'          => 'fa-tooth',
        'pneumologia'          => 'fa-lungs',
        'gastroenterologia'    => 'fa-stethoscope',
        'ortopedia'            => 'fa-bone',
        'pediatria'            => 'fa-child',
        'ginecologia'          => 'fa-venus',
        'neurologia'           => 'fa-brain',
        'psiquiatria'          => 'fa-comment-dots',
        'endocrinologia'       => 'fa-scale-balanced',
        'urologia'             => 'fa-x-ray',
        'nutrição'             => 'fa-bowl-food',
        'fisioterapia'         => 'fa-person-walking',
        'otorrinolaringologia' => 'fa-ear-listen',
        'clínica geral'        => 'fa-bandage',
    ];

    $chave = mb_strtolower($nome, 'UTF-8');
    foreach ($mapa as $termo => $classe) {
        if (mb_strpos($chave, $termo) !== false) {
            return '<i class="fa-solid ' . $classe . '" aria-hidden="true"></i>';
        }
    }
    return '<i class="fa-solid fa-hospital" aria-hidden="true"></i>';
}

/**
 * Obter ícone representativo de um exame (Font Awesome)
 * @param string $nome
 * @return string
 */
function obter_icone_exame($nome) {
    $mapa = [
        'eletrocardiograma' => 'fa-heart',
        'ultrassom'         => 'fa-satellite-dish',
        'ressonância'       => 'fa-magnet',
        'ressonancia'       => 'fa-magnet',
        'tomografia'        => 'fa-x-ray',
        'hemograma'         => 'fa-droplet',
        'sangue'            => 'fa-droplet',
        'diabetes'          => 'fa-syringe',
        'glicemia'          => 'fa-syringe',
        'raio'              => 'fa-x-ray',
        'urina'             => 'fa-flask',
        'biópsia'           => 'fa-microscope',
        'biopsia'           => 'fa-microscope',
        'mamografia'        => 'fa-x-ray',
    ];

    $chave = mb_strtolower($nome, 'UTF-8');
    foreach ($mapa as $termo => $classe) {
        if (mb_strpos($chave, $termo) !== false) {
            return '<i class="fa-solid ' . $classe . '" aria-hidden="true"></i>';
        }
    }
    return '<i class="fa-solid fa-dna" aria-hidden="true"></i>';
}

/**
 * Obter tempo estimado de atendimento para uma especialidade.
 * Usa a média do intervalo_minutos dos médicos daquela especialidade;
 * cai para 30 min se não houver horários cadastrados.
 * @param int $id  id da especialidade
 * @return string  ex: "30 min" ou "1h" ou "1h15min"
 */
function obter_tempo_estimado($id) {
    try {
        $db   = Conexao::getInstance()->getConexao();
        $stmt = $db->prepare(
            'SELECT AVG(ha.intervalo_minutos) AS media
             FROM horarios_atendimento ha
             JOIN medicos m ON ha.id_medico = m.id
             WHERE m.id_especialidade = ? AND ha.ativo = 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $minutos = (int) round($row['media'] ?? 30);
        if ($minutos <= 0) $minutos = 30;
        if ($minutos >= 60) {
            $h = intdiv($minutos, 60);
            $m = $minutos % 60;
            return $m > 0 ? "{$h}h{$m}min" : "{$h}h";
        }
        return "{$minutos} min";
    } catch (Exception $e) {
        return '30 min';
    }
}

/**
 * Indicar se uma especialidade deve exibir o selo "Mais agendado".
 * Retorna true quando o número de agendamentos da especialidade
 * supera a média de agendamentos por especialidade.
 * @param int $id  id da especialidade
 * @return bool
 */
function eh_popular($id) {
    try {
        $db = Conexao::getInstance()->getConexao();

        // Total de agendamentos desta especialidade
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS total FROM agendamentos
             WHERE id_especialidade = ? AND status != 'cancelado'"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Média geral por especialidade (subquery)
        $stmt2 = $db->prepare(
            "SELECT AVG(cnt) AS media FROM (
                SELECT COUNT(*) AS cnt FROM agendamentos
                WHERE status != 'cancelado'
                GROUP BY id_especialidade
            ) sub"
        );
        $stmt2->execute();
        $media = (float) ($stmt2->get_result()->fetch_assoc()['media'] ?? 0);
        $stmt2->close();

        return $total > 0 && $total > $media;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obter variação percentual fictícia para métricas do
 * dashboard administrativo (apenas visual)
 * @param int $semente
 * @return array{texto: string, positivo: bool}
 */
function obter_variacao_percentual($semente) {
    $valores = [12, 8, -5, 20, 3, -2, 15];
    $variacao = $valores[$semente % count($valores)];
    return [
        'texto' => ($variacao >= 0 ? '+' : '') . $variacao . '% este mês',
        'positivo' => $variacao >= 0,
    ];
}

/**
 * Formatar percentual para exibição (ex: 70 -> "70%")
 * @param float $valor
 * @return string
 */
function formatar_percentual($valor) {
    $valor = (float) $valor;
    $formatado = rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    return $formatado . '%';
}

/**
 * Calcular o valor que cabe ao médico sobre um valor total,
 * de acordo com o percentual de repasse configurado.
 * @param float $valor_total
 * @param float $percentual
 * @return float
 */
function calcular_valor_medico($valor_total, $percentual) {
    return round(((float) $valor_total) * ((float) $percentual) / 100, 2);
}

/**
 * Obter nome do dia da semana em português
 * @param int $dia_semana (0=domingo a 6=sábado)
 * @return string
 */
function obter_nome_dia_semana($dia_semana) {
    $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    return $dias[$dia_semana % 7] ?? '';
}

/**
 * Obter abreviação do dia da semana em português
 * @param int $dia_semana (0=domingo a 6=sábado)
 * @return string
 */
function obter_abrev_dia_semana($dia_semana) {
    $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    return $dias[$dia_semana % 7] ?? '';
}

/**
 * Formatar horário (HH:MM:SS ou HH:MM) para exibição (HH:MM)
 * @param string $hora
 * @return string
 */
function formatar_hora($hora) {
    return substr($hora, 0, 5);
}

/**
 * Obter rótulo em português para o status de pagamento
 * de um agendamento ao médico
 * @param string $status
 * @return string
 */
function obter_label_status_pagamento($status) {
    $mapa = [
        'pendente' => 'Pendente',
        'pago_clinica' => 'Recebido pela clínica',
        'pago_medico' => 'Repassado ao médico',
    ];
    return $mapa[$status] ?? $status;
}

/**
 * Obter classe CSS de badge para o status de pagamento
 * @param string $status
 * @return string
 */
function obter_classe_status_pagamento($status) {
    $mapa = [
        'pendente' => 'badge-warning',
        'pago_clinica' => 'badge-info',
        'pago_medico' => 'badge-success',
    ];
    return $mapa[$status] ?? 'badge-secondary';
}

/**
 * Gerar lista de horários (HH:MM) entre hora_inicio e hora_fim,
 * respeitando o intervalo (em minutos) entre cada consulta.
 * O último slot só é incluído se a consulta inteira couber
 * antes de hora_fim.
 * @param string $hora_inicio (HH:MM ou HH:MM:SS)
 * @param string $hora_fim (HH:MM ou HH:MM:SS)
 * @param int $intervalo_minutos
 * @return string[] lista de horários no formato HH:MM
 */
function gerar_slots_horario($hora_inicio, $hora_fim, $intervalo_minutos) {
    $slots = [];
    $intervalo_minutos = max(1, (int) $intervalo_minutos);

    try {
        $atual = DateTime::createFromFormat('H:i', substr($hora_inicio, 0, 5));
        $fim = DateTime::createFromFormat('H:i', substr($hora_fim, 0, 5));
    } catch (Exception $e) {
        return $slots;
    }

    if (!$atual || !$fim) {
        return $slots;
    }

    while (true) {
        $proximo = clone $atual;
        $proximo->modify('+' . $intervalo_minutos . ' minutes');

        if ($proximo > $fim) {
            break;
        }

        $slots[] = $atual->format('H:i');
        $atual = $proximo;
    }

    return $slots;
}

?>
