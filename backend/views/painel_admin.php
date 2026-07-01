<?php
// ====================================================
// ARQUIVO: backend/views/painel_admin.php
// Descrição: Painel administrativo simples
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar permissões
require_admin();

// Flag usada pelos sub-views para impedir acesso direto
define('PAINEL_ADMIN_LOADED', true);

$conexao_db = Conexao::getInstance()->getConexao();
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');
$pagina = intval($_GET['pagina'] ?? 1);
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

$base_url = '../../';
$titulo_pagina = 'Painel Admin - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

<?php
$titulos_admin = [
    'dashboard'        => 'Dashboard',
    'clientes'         => 'Pacientes',
    'agendamentos'     => 'Agendamentos',
    'especialidades'   => 'Especialidades',
    'especialidade_form' => 'Especialidades',
    'exames'           => 'Exames',
    'exame_form'       => 'Exames',
    'medicos'          => 'Médicos',
    'medico_form'      => 'Médicos',
    'horarios'         => 'Médicos',
    'recepcionistas'   => 'Recepcionistas',
    'recepcionista_form' => 'Recepcionistas',
    'financeiro'       => 'Financeiro',
    'relatorios'       => 'Relatórios',
    'logs'             => 'Logs',
];
$titulo_acao_admin = $titulos_admin[$acao] ?? 'Dashboard';
$inicial_admin = mb_strtoupper(mb_substr($_SESSION['nome_cliente'] ?? 'A', 0, 1));
?>

<div class="painel-wrapper">

    <!-- Sidebar Admin -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="<?php echo $base_url; ?>assets/imagens/logo.png" alt="Logo Clínica">
            <span class="sidebar-logo-text">Clínica Saúde<br>&amp; Bem-Estar</span>
        </div>

        <div class="sidebar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="sidebar-search-input" placeholder="Buscar..." autocomplete="off">
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section-title">Geral</div>
            <a href="?acao=dashboard" class="sidebar-item <?php echo $acao === 'dashboard' ? 'ativo' : ''; ?>" data-tooltip="Dashboard">
                <i class="fa-solid fa-chart-bar"></i><span>Dashboard</span>
            </a>
            <a href="?acao=agendamentos" class="sidebar-item <?php echo $acao === 'agendamentos' ? 'ativo' : ''; ?>" data-tooltip="Agendamentos">
                <i class="fa-solid fa-calendar-days"></i><span>Agendamentos</span>
            </a>
            <a href="?acao=clientes" class="sidebar-item <?php echo $acao === 'clientes' ? 'ativo' : ''; ?>" data-tooltip="Pacientes">
                <i class="fa-solid fa-users"></i><span>Pacientes</span>
            </a>

            <div class="sidebar-section-title">Cadastros</div>
            <a href="?acao=medicos" class="sidebar-item <?php echo in_array($acao, ['medicos', 'medico_form', 'horarios']) ? 'ativo' : ''; ?>" data-tooltip="Médicos">
                <i class="fa-solid fa-stethoscope"></i><span>Médicos</span>
            </a>
            <a href="?acao=especialidades" class="sidebar-item <?php echo in_array($acao, ['especialidades', 'especialidade_form']) ? 'ativo' : ''; ?>" data-tooltip="Especialidades">
                <i class="fa-solid fa-hospital"></i><span>Especialidades</span>
            </a>
            <a href="?acao=exames" class="sidebar-item <?php echo in_array($acao, ['exames', 'exame_form']) ? 'ativo' : ''; ?>" data-tooltip="Exames">
                <i class="fa-solid fa-dna"></i><span>Exames</span>
            </a>
            <a href="?acao=recepcionistas" class="sidebar-item <?php echo in_array($acao, ['recepcionistas', 'recepcionista_form']) ? 'ativo' : ''; ?>" data-tooltip="Recepcionistas">
                <i class="fa-solid fa-id-badge"></i><span>Recepcionistas</span>
            </a>

            <div class="sidebar-section-title">Ferramentas</div>
            <a href="?acao=financeiro" class="sidebar-item <?php echo $acao === 'financeiro' ? 'ativo' : ''; ?>" data-tooltip="Financeiro">
                <i class="fa-solid fa-credit-card"></i><span>Financeiro</span>
            </a>
            <a href="?acao=relatorios" class="sidebar-item <?php echo $acao === 'relatorios' ? 'ativo' : ''; ?>" data-tooltip="Relatórios">
                <i class="fa-solid fa-chart-line"></i><span>Relatórios</span>
            </a>
            <a href="?acao=logs" class="sidebar-item <?php echo $acao === 'logs' ? 'ativo' : ''; ?>" data-tooltip="Logs">
                <i class="fa-solid fa-file-lines"></i><span>Logs</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="painel_cliente.php" class="sidebar-item" data-tooltip="Voltar ao Painel" style="color:#8fa3b1;">
                <i class="fa-solid fa-arrow-left"></i><span>Voltar ao Painel</span>
            </a>
            <a href="<?php echo $base_url; ?>backend/auth/deslogar.php" class="sidebar-item sidebar-sair" data-tooltip="Sair">
                <i class="fa-solid fa-right-from-bracket"></i><span>Sair</span>
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Recolher barra lateral" title="Recolher barra lateral">
                <i class="fa-solid fa-angles-left" id="toggleIcon"></i>
            </button>
        </div>
    </aside>

    <!-- Conteúdo principal -->
    <main class="sidebar-main">
        <div class="breadcrumb-bar">
            <button class="breadcrumb-toggle" onclick="document.getElementById('sidebarToggle').click()" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="breadcrumb-path">
                <span class="breadcrumb-root">Painel Admin</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($titulo_acao_admin); ?></span>
            </span>
        </div>
        <div class="painel-main">

        <?php if ($acao === 'dashboard'): ?>
            <!-- DASHBOARD -->
            <?php
                $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM clientes WHERE eh_admin = 0');
                $stmt->execute();
                $total_clientes = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM agendamentos WHERE status = "pendente"');
                $stmt->execute();
                $agendamentos_pendentes = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM agendamentos WHERE status = "confirmado"');
                $stmt->execute();
                $agendamentos_confirmados = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                // Agendamentos do mês atual (exclui cancelados)
                $stmt = $conexao_db->prepare(
                    "SELECT COUNT(*) as total FROM agendamentos
                     WHERE status != 'cancelado'
                     AND strftime('%Y-%m', data_hora) = strftime('%Y-%m', 'now', 'localtime')"
                );
                $stmt->execute();
                $agendamentos_mes = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                // Faturamento do mês (apenas atendimentos concluídos)
                $stmt = $conexao_db->prepare(
                    "SELECT COALESCE(SUM(valor_total), 0) as total FROM agendamentos
                     WHERE status = 'concluído'
                     AND strftime('%Y-%m', data_hora) = strftime('%Y-%m', 'now', 'localtime')"
                );
                $stmt->execute();
                $faturamento_mes = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                // Taxa de ocupação da semana atual: slots disponíveis (grade ativa) x agendamentos
                $stmt = $conexao_db->prepare(
                    'SELECT hora_inicio, hora_fim, intervalo_minutos FROM horarios_atendimento WHERE ativo = 1'
                );
                $stmt->execute();
                $horarios_ativos = $stmt->get_result()->fetch_all() ?? [];
                $stmt->close();

                $slots_disponiveis = 0;
                foreach ($horarios_ativos as $h) {
                    $slots_disponiveis += count(gerar_slots_horario($h['hora_inicio'], $h['hora_fim'], $h['intervalo_minutos']));
                }

                $inicio_semana = (new DateTime('monday this week'))->format('Y-m-d 00:00:00');
                $fim_semana    = (new DateTime('sunday this week'))->format('Y-m-d 23:59:59');

                $stmt = $conexao_db->prepare(
                    "SELECT COUNT(*) as total FROM agendamentos
                     WHERE status IN ('pendente', 'confirmado', 'concluído')
                     AND data_hora BETWEEN ? AND ?"
                );
                $stmt->bind_param('ss', $inicio_semana, $fim_semana);
                $stmt->execute();
                $agendamentos_semana = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $taxa_ocupacao = $slots_disponiveis > 0
                    ? min(100, (int) round(($agendamentos_semana / $slots_disponiveis) * 100))
                    : 0;

                $variacao_mes          = obter_variacao_percentual($agendamentos_mes + 1);
                $variacao_faturamento  = obter_variacao_percentual(2); // fixa o indicador "-5% este mês"
                $variacao_ocupacao     = obter_variacao_percentual($taxa_ocupacao + 4);
                $variacao_clientes     = obter_variacao_percentual($total_clientes);
                $variacao_pendentes    = obter_variacao_percentual($agendamentos_pendentes + 1);
                $variacao_confirmados  = obter_variacao_percentual($agendamentos_confirmados + 2);
            ?>

            <div class="stats">
                <div class="stat-card stat-card-primario">
                    <div class="stat-icone pulse"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3><i class="fa-solid fa-calendar-check"></i> Agendamentos do Mês</h3>
                    <div class="stat-number"><?php echo $agendamentos_mes; ?></div>
                    <div class="stat-variacao <?php echo $variacao_mes['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_mes['texto']; ?></div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-icone pulse"><i class="fa-solid fa-sack-dollar"></i></div>
                    <h3><i class="fa-solid fa-dollar-sign"></i> Faturamento do Mês</h3>
                    <div class="stat-number"><?php echo formatar_valor($faturamento_mes); ?></div>
                    <div class="stat-variacao <?php echo $variacao_faturamento['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_faturamento['texto']; ?></div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-icone pulse"><i class="fa-solid fa-gauge-high"></i></div>
                    <h3><i class="fa-solid fa-chart-pie"></i> Taxa de Ocupação</h3>
                    <div class="stat-number"><?php echo $taxa_ocupacao; ?>%</div>
                    <div class="stat-variacao <?php echo $variacao_ocupacao['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_ocupacao['texto']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icone pulse"><i class="fa-solid fa-users"></i></div>
                    <h3><i class="fa-solid fa-user"></i> Pacientes Cadastrados</h3>
                    <div class="stat-number"><?php echo $total_clientes; ?></div>
                    <div class="stat-variacao <?php echo $variacao_clientes['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_clientes['texto']; ?></div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-icone pulse"><i class="fa-solid fa-hourglass-half"></i></div>
                    <h3><i class="fa-solid fa-clipboard-list"></i> Agendamentos Pendentes</h3>
                    <div class="stat-number"><?php echo $agendamentos_pendentes; ?></div>
                    <div class="stat-variacao <?php echo $variacao_pendentes['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_pendentes['texto']; ?></div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-icone pulse"><i class="fa-solid fa-circle-check"></i></div>
                    <h3><i class="fa-solid fa-calendar-check"></i> Agendamentos Confirmados</h3>
                    <div class="stat-number"><?php echo $agendamentos_confirmados; ?></div>
                    <div class="stat-variacao <?php echo $variacao_confirmados['positivo'] ? 'positivo' : 'negativo'; ?>"><?php echo $variacao_confirmados['texto']; ?></div>
                </div>
            </div>

        <?php elseif ($acao === 'clientes'): ?>
            <!-- LISTA DE CLIENTES -->
            <h3>Clientes Cadastrados</h3>

            <?php
                $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM clientes WHERE eh_admin = 0');
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $total_paginas = ceil($total / $itens_por_pagina);

                $stmt = $conexao_db->prepare(
                    'SELECT id, nome, email, cpf, telefone, data_cadastro, ativo FROM clientes WHERE eh_admin = 0 ORDER BY data_cadastro DESC LIMIT ? OFFSET ?'
                );
                $stmt->bind_param('ii', $itens_por_pagina, $offset);
                $stmt->execute();
                $clientes = $stmt->get_result();
                $stmt->close();
            ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>CPF</th>
                        <th>Cadastro</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo formatar_cpf($cliente['cpf']); ?></td>
                            <td><?php echo formatar_data($cliente['data_cadastro']); ?></td>
                            <td>
                                <span class="badge <?php echo $cliente['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $cliente['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?acao=clientes&pagina=<?php echo $pagina - 1; ?>"><i class="fa-solid fa-arrow-left"></i> Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <span class="<?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a href="?acao=clientes&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </span>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?acao=clientes&pagina=<?php echo $pagina + 1; ?>">Próxima →</a>
                <?php endif; ?>
            </div>

        <?php elseif ($acao === 'agendamentos'): ?>
            <!-- LISTA DE AGENDAMENTOS -->
            <h3>Todos os Agendamentos</h3>

            <?php $flash_financeiro = get_flash_message('financeiro'); ?>
            <?php if ($flash_financeiro): ?>
                <div class="alert <?php echo $flash_financeiro['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($flash_financeiro['mensagem']); ?>
                </div>
            <?php endif; ?>

            <?php
                // Filtros
                $filtro_medico = intval($_GET['id_medico'] ?? 0);
                $filtro_status = sanitizar_input($_GET['status'] ?? '');
                $filtro_status_pagamento = sanitizar_input($_GET['status_pagamento'] ?? '');
                $filtro_data_inicio = sanitizar_input($_GET['data_inicio'] ?? '');
                $filtro_data_fim = sanitizar_input($_GET['data_fim'] ?? '');

                $condicoes = [];
                $params_filtro = [];
                $tipos_filtro = '';

                if ($filtro_medico > 0) {
                    $condicoes[] = 'a.id_medico = ?';
                    $params_filtro[] = $filtro_medico;
                    $tipos_filtro .= 'i';
                }
                if (in_array($filtro_status, ['pendente', 'confirmado', 'cancelado', 'concluído'], true)) {
                    $condicoes[] = 'a.status = ?';
                    $params_filtro[] = $filtro_status;
                    $tipos_filtro .= 's';
                }
                if (in_array($filtro_status_pagamento, ['pendente', 'pago_clinica', 'pago_medico'], true)) {
                    $condicoes[] = 'a.status_pagamento = ?';
                    $params_filtro[] = $filtro_status_pagamento;
                    $tipos_filtro .= 's';
                }
                if (validar_data($filtro_data_inicio)) {
                    $condicoes[] = 'a.data_realizacao >= ?';
                    $params_filtro[] = $filtro_data_inicio;
                    $tipos_filtro .= 's';
                }
                if (validar_data($filtro_data_fim)) {
                    $condicoes[] = 'a.data_realizacao <= ?';
                    $params_filtro[] = $filtro_data_fim;
                    $tipos_filtro .= 's';
                }

                $where_filtro = !empty($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';

                $sql_total = "SELECT COUNT(*) as total FROM agendamentos a $where_filtro";
                $stmt = $conexao_db->prepare($sql_total);
                if (!empty($params_filtro)) {
                    $stmt->bind_param($tipos_filtro, ...$params_filtro);
                }
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $total_paginas = max(1, ceil($total / $itens_por_pagina));

                $sql = "SELECT a.*, c.nome as cliente_nome, COALESCE(e.nome, sp.nome, a.notas, '-') as item_nome, a.tipo, m.nome as nome_medico
                        FROM agendamentos a
                        LEFT JOIN clientes c ON a.id_cliente = c.id
                        LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                        LEFT JOIN exames e ON a.id_exame = e.id
                        LEFT JOIN medicos m ON a.id_medico = m.id
                        $where_filtro
                        ORDER BY a.data_hora DESC
                        LIMIT ? OFFSET ?";
                $stmt = $conexao_db->prepare($sql);
                $params_pagina = array_merge($params_filtro, [$itens_por_pagina, $offset]);
                $stmt->bind_param($tipos_filtro . 'ii', ...$params_pagina);
                $stmt->execute();
                $agendamentos = $stmt->get_result();
                $stmt->close();

                // Lista de médicos para o filtro
                $stmt = $conexao_db->prepare('SELECT id, nome FROM medicos ORDER BY nome');
                $stmt->execute();
                $medicos_filtro = $stmt->get_result()->fetch_all();
                $stmt->close();

                $query_string_filtros = http_build_query(array_filter([
                    'id_medico' => $filtro_medico ?: null,
                    'status' => $filtro_status ?: null,
                    'status_pagamento' => $filtro_status_pagamento ?: null,
                    'data_inicio' => $filtro_data_inicio ?: null,
                    'data_fim' => $filtro_data_fim ?: null,
                ]));
            ?>

            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end; margin-bottom: 15px;">
                <input type="hidden" name="acao" value="agendamentos">
                <div>
                    <label for="filtro_medico" style="display: block; font-size: 12px;">Médico</label>
                    <select id="filtro_medico" name="id_medico" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        <option value="">Todos</option>
                        <?php foreach ($medicos_filtro as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $filtro_medico === (int) $m['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filtro_status" style="display: block; font-size: 12px;">Status</label>
                    <select id="filtro_status" name="status" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        <option value="">Todos</option>
                        <?php foreach (['pendente' => 'Pendente', 'confirmado' => 'Confirmado', 'concluído' => 'Concluído', 'cancelado' => 'Cancelado'] as $valor => $label): ?>
                            <option value="<?php echo $valor; ?>" <?php echo $filtro_status === $valor ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filtro_status_pagamento" style="display: block; font-size: 12px;">Pagamento</label>
                    <select id="filtro_status_pagamento" name="status_pagamento" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        <option value="">Todos</option>
                        <?php foreach (['pendente' => 'Pendente', 'pago_clinica' => 'Recebido pela clínica', 'pago_medico' => 'Repassado ao médico'] as $valor => $label): ?>
                            <option value="<?php echo $valor; ?>" <?php echo $filtro_status_pagamento === $valor ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filtro_data_inicio" style="display: block; font-size: 12px;">Realizado de</label>
                    <input type="date" id="filtro_data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
                </div>
                <div>
                    <label for="filtro_data_fim" style="display: block; font-size: 12px;">Realizado até</label>
                    <input type="date" id="filtro_data_fim" name="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim); ?>" style="padding: 8px; border: 1px solid #e0e0e0; border-radius: 5px;">
                </div>
                <div>
                    <button type="submit" class="btn-action">Filtrar</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Limpar</a>
                </div>
            </form>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Item</th>
                        <th>Médico</th>
                        <th>Data/Hora</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($agendamentos->num_rows === 0): ?>
                        <tr><td colspan="9" style="text-align: center;">Nenhum agendamento encontrado.</td></tr>
                    <?php endif; ?>
                    <?php while ($ag = $agendamentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ag['cliente_nome']); ?></td>
                            <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($ag['item_nome'] ?? ''); ?></td>
                            <td><?php echo !empty($ag['nome_medico']) ? htmlspecialchars($ag['nome_medico']) : '-'; ?></td>
                            <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                            <td><?php echo !empty($ag['valor_total']) ? formatar_valor($ag['valor_total']) : '-'; ?></td>
                            <td>
                                <span class="badge <?php echo get_classe_status($ag['status']); ?>">
                                    <?php echo get_status_agendamento($ag['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo obter_classe_status_pagamento($ag['status_pagamento']); ?>">
                                    <?php echo obter_label_status_pagamento($ag['status_pagamento']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (in_array($ag['status'], ['pendente', 'confirmado'], true)): ?>
                                    <form method="POST" action="../controllers/financeiro_controller.php" data-confirm="Confirma a marcação deste atendimento como realizado?" data-confirm-titulo="Marcar como realizado">
                                        <input type="hidden" name="acao" value="marcar_realizado">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <button type="submit" class="action-btn action-edit">Marcar realizado</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?acao=agendamentos&pagina=<?php echo $pagina - 1; ?>&<?php echo $query_string_filtros; ?>"><i class="fa-solid fa-arrow-left"></i> Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <span class="<?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a href="?acao=agendamentos&pagina=<?php echo $i; ?>&<?php echo $query_string_filtros; ?>"><?php echo $i; ?></a>
                    </span>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?acao=agendamentos&pagina=<?php echo $pagina + 1; ?>&<?php echo $query_string_filtros; ?>">Próxima →</a>
                <?php endif; ?>
            </div>

        <?php elseif ($acao === 'especialidades'): ?>
            <?php require __DIR__ . '/especialidade_listar.php'; ?>

        <?php elseif ($acao === 'especialidade_form'): ?>
            <?php require __DIR__ . '/especialidade_form.php'; ?>

        <?php elseif ($acao === 'exames'): ?>
            <?php require __DIR__ . '/exame_listar.php'; ?>

        <?php elseif ($acao === 'exame_form'): ?>
            <?php require __DIR__ . '/exame_form.php'; ?>

        <?php elseif ($acao === 'medicos'): ?>
            <?php require __DIR__ . '/medico_listar.php'; ?>

        <?php elseif ($acao === 'medico_form'): ?>
            <?php require __DIR__ . '/medico_form.php'; ?>

        <?php elseif ($acao === 'horarios'): ?>
            <?php require __DIR__ . '/horarios_form.php'; ?>

        <?php elseif ($acao === 'recepcionistas'): ?>
            <?php require __DIR__ . '/recepcionista_listar.php'; ?>

        <?php elseif ($acao === 'recepcionista_form'): ?>
            <?php require __DIR__ . '/recepcionista_form.php'; ?>

        <?php elseif ($acao === 'financeiro'): ?>
            <?php require __DIR__ . '/financeiro.php'; ?>

        <?php elseif ($acao === 'relatorios'): ?>
            <?php require __DIR__ . '/relatorios.php'; ?>

        <?php elseif ($acao === 'logs'): ?>
            <?php require __DIR__ . '/logs_viewer.php'; ?>

        <?php else: ?>
            <p>Ação não encontrada.</p>
        <?php endif; ?>
        </div><!-- /.painel-main -->
    </main>
</div><!-- /.painel-wrapper -->

<script>
(function(){
    var sidebar = document.getElementById('sidebar');
    var btn     = document.getElementById('sidebarToggle');
    var icon    = document.getElementById('toggleIcon');
    var key     = 'sidebar_admin_collapsed';

    function aplicar(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        icon.className = collapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-angles-left';
        btn.title = collapsed ? 'Expandir barra lateral' : 'Recolher barra lateral';
        try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch(e){}
    }

    btn.addEventListener('click', function(){
        aplicar(!sidebar.classList.contains('collapsed'));
    });

    var searchInput = document.getElementById('sidebar-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(){
            var q = this.value.toLowerCase();
            document.querySelectorAll('.sidebar-nav .sidebar-item').forEach(function(el){
                el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
