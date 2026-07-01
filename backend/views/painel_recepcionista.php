<?php
// ====================================================
// ARQUIVO: backend/views/painel_recepcionista.php
// Descrição: Painel da recepcionista
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_recepcionista();

$conexao_db = Conexao::getInstance()->getConexao();
$acao       = sanitizar_input($_GET['acao'] ?? 'dashboard');
$pagina     = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 15;
$offset     = ($pagina - 1) * $por_pagina;

$nome_recep = $_SESSION['nome_cliente'];

$base_url     = '../../';
$titulo_pagina = 'Painel da Recepcionista - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

<?php $flash_login = get_flash_message('login'); ?>
<?php $flash_recep = get_flash_message('recepcionista'); ?>
<?php if ($flash_login || $flash_recep): ?>
<div class="flash-container" role="status" aria-live="polite">
    <?php foreach (array_filter([$flash_login, $flash_recep]) as $f): ?>
        <div class="flash-toast flash-<?php echo $f['tipo'] === 'sucesso' ? 'sucesso' : 'erro'; ?>">
            <span class="flash-toast-icone"><?php echo $f['tipo'] === 'sucesso' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>'; ?></span>
            <span class="flash-toast-texto"><?php echo htmlspecialchars($f['mensagem']); ?></span>
            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
            <span class="flash-toast-progresso"></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="painel-wrapper">

    <!-- Sidebar colapsável -->
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
            <a href="?acao=dashboard" class="sidebar-item <?php echo $acao === 'dashboard' ? 'ativo' : ''; ?>" data-tooltip="Dashboard">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Dashboard</span>
            </a>
            <a href="?acao=agendamentos" class="sidebar-item <?php echo $acao === 'agendamentos' ? 'ativo' : ''; ?>" data-tooltip="Agendamentos">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Agendamentos</span>
            </a>
            <a href="?acao=novo_agendamento" class="sidebar-item <?php echo $acao === 'novo_agendamento' ? 'ativo' : ''; ?>" data-tooltip="Novo Agendamento">
                <i class="fa-solid fa-plus"></i>
                <span>Novo Agendamento</span>
            </a>
            <a href="?acao=clientes" class="sidebar-item <?php echo $acao === 'clientes' ? 'ativo' : ''; ?>" data-tooltip="Pacientes">
                <i class="fa-solid fa-users"></i>
                <span>Pacientes</span>
            </a>
            <a href="?acao=medicos" class="sidebar-item <?php echo $acao === 'medicos' ? 'ativo' : ''; ?>" data-tooltip="Médicos">
                <i class="fa-solid fa-stethoscope"></i>
                <span>Médicos</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?php echo $base_url; ?>backend/auth/deslogar.php" class="sidebar-item sidebar-sair" data-tooltip="Sair">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sair</span>
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Recolher barra lateral" title="Recolher barra lateral">
                <i class="fa-solid fa-angles-left" id="toggleIcon"></i>
            </button>
        </div>
    </aside>

<?php
$titulos_recep = [
    'dashboard'        => 'Dashboard',
    'agendamentos'     => 'Agendamentos',
    'novo_agendamento' => 'Novo Agendamento',
    'clientes'         => 'Pacientes',
    'medicos'          => 'Médicos',
];
$titulo_acao_recep = $titulos_recep[$acao] ?? 'Dashboard';
?>

    <!-- Main -->
    <main class="sidebar-main">
        <div class="breadcrumb-bar">
            <button class="breadcrumb-toggle" onclick="document.getElementById('sidebarToggle').click()" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="breadcrumb-path">
                <span class="breadcrumb-root">Painel Recepcionista</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($titulo_acao_recep); ?></span>
            </span>
        </div>
        <div class="painel-main">

        <?php if (!empty($_SESSION['erros_recep'])): ?>
            <div class="alert alert-error">
                <?php foreach ($_SESSION['erros_recep'] as $e): ?>
                    <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['erros_recep']); ?>
        <?php endif; ?>

        <?php /* ===== DASHBOARD ===== */ if ($acao === 'dashboard'): ?>
            <?php
            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM agendamentos WHERE DATE(data_hora) = DATE('now','localtime') AND status IN ('pendente','confirmado')");
            $stmt->execute(); $hoje = $stmt->get_result()->fetch_assoc()['t']; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM agendamentos WHERE status = 'pendente'");
            $stmt->execute(); $pendentes = $stmt->get_result()->fetch_assoc()['t']; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM agendamentos WHERE status = 'confirmado' AND data_hora >= datetime('now','localtime')");
            $stmt->execute(); $confirmados = $stmt->get_result()->fetch_assoc()['t']; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM clientes WHERE tipo = 'cliente'");
            $stmt->execute(); $total_clientes = $stmt->get_result()->fetch_assoc()['t']; $stmt->close();

            // Próximas do dia
            $stmt = $conexao_db->prepare(
                "SELECT a.*, c.nome AS nome_cliente, m.nome AS nome_medico
                 FROM agendamentos a
                 JOIN clientes c ON c.id = a.id_cliente
                 LEFT JOIN medicos m ON m.id = a.id_medico
                 WHERE DATE(a.data_hora) = DATE('now','localtime')
                 AND a.status IN ('pendente','confirmado')
                 ORDER BY a.data_hora ASC LIMIT 20"
            );
            $stmt->execute(); $agenda_hoje = $stmt->get_result()->fetch_all() ?? []; $stmt->close();
            ?>

            <h2>Dashboard</h2>

            <div class="painel-cards">
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="painel-card-valor"><?php echo $hoje; ?></div>
                    <div class="painel-card-label">Agendamentos hoje</div>
                </div>
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="painel-card-valor"><?php echo $pendentes; ?></div>
                    <div class="painel-card-label">Pendentes</div>
                </div>
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="painel-card-valor"><?php echo $confirmados; ?></div>
                    <div class="painel-card-label">Confirmados</div>
                </div>
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-users"></i></div>
                    <div class="painel-card-valor"><?php echo $total_clientes; ?></div>
                    <div class="painel-card-label">Pacientes</div>
                </div>
            </div>

            <h3>Agenda de hoje</h3>
            <?php if (empty($agenda_hoje)): ?>
                <div class="alert alert-info">Nenhum agendamento para hoje.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tabela-admin">
                        <thead><tr><th>Hora</th><th>Paciente</th><th>Médico</th><th>Tipo</th><th>Status</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php foreach ($agenda_hoje as $ag): ?>
                            <tr>
                                <td><strong><?php echo date('H:i', strtotime($ag['data_hora'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($ag['nome_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($ag['nome_medico'] ?? '—'); ?></td>
                                <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                <td>
                                    <?php if ($ag['status'] === 'pendente'): ?>
                                    <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <input type="hidden" name="acao" value="confirmar_agendamento">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <button class="btn-action" style="padding:4px 10px;font-size:0.8rem;">Confirmar</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($ag['status'], ['pendente','confirmado'])): ?>
                                    <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <input type="hidden" name="acao" value="cancelar_agendamento">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <button class="btn-action secondary" style="padding:4px 10px;font-size:0.8rem;" onclick="return confirm('Cancelar este agendamento?')">Cancelar</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php /* ===== AGENDAMENTOS ===== */ elseif ($acao === 'agendamentos'): ?>
            <?php
            $busca = sanitizar_input($_GET['busca'] ?? '');
            $filtro_status = sanitizar_input($_GET['status'] ?? '');

            $where = '1=1';
            $params = [];
            $tipos  = '';

            if (!empty($busca)) {
                $where .= " AND (c.nome LIKE ? OR c.email LIKE ?)";
                $params[] = "%$busca%"; $params[] = "%$busca%";
                $tipos .= 'ss';
            }
            if (!empty($filtro_status)) {
                $where .= " AND a.status = ?";
                $params[] = $filtro_status; $tipos .= 's';
            }

            $sql_count = "SELECT COUNT(*) as t FROM agendamentos a JOIN clientes c ON c.id=a.id_cliente WHERE $where";
            $stmt = $conexao_db->prepare($sql_count);
            if (!empty($params)) { $stmt->bind_param($tipos, ...$params); }
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['t'];
            $stmt->close();

            $total_paginas = max(1, ceil($total / $por_pagina));

            $sql = "SELECT a.*, c.nome AS nome_cliente, m.nome AS nome_medico
                    FROM agendamentos a
                    JOIN clientes c ON c.id=a.id_cliente
                    LEFT JOIN medicos m ON m.id=a.id_medico
                    WHERE $where ORDER BY a.data_hora DESC LIMIT $por_pagina OFFSET $offset";
            $stmt = $conexao_db->prepare($sql);
            if (!empty($params)) { $stmt->bind_param($tipos, ...$params); }
            $stmt->execute();
            $agendamentos = $stmt->get_result()->fetch_all() ?? [];
            $stmt->close();
            ?>

            <h2>Agendamentos</h2>

            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <input type="hidden" name="acao" value="agendamentos">
                <input type="text" name="busca" placeholder="Buscar paciente..." value="<?php echo htmlspecialchars($busca); ?>"
                       style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;flex:1;min-width:180px;">
                <select name="status" style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                    <option value="">Todos os status</option>
                    <?php foreach (['pendente','confirmado','cancelado','concluído'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $filtro_status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-action" type="submit">Filtrar</button>
                <a href="?acao=agendamentos" class="btn-action secondary">Limpar</a>
            </form>

            <div class="table-responsive">
                <table class="tabela-admin">
                    <thead><tr><th>Data/Hora</th><th>Paciente</th><th>Médico</th><th>Tipo</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php if (empty($agendamentos)): ?>
                            <tr><td colspan="6" style="text-align:center;color:#999;">Nenhum agendamento encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($agendamentos as $ag): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($ag['data_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($ag['nome_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($ag['nome_medico'] ?? '—'); ?></td>
                                <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                <td style="white-space:nowrap;">
                                    <?php if ($ag['status'] === 'pendente'): ?>
                                    <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <input type="hidden" name="acao" value="confirmar_agendamento">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <input type="hidden" name="redirect" value="agendamentos">
                                        <button class="btn-action" style="padding:4px 10px;font-size:0.8rem;">✔ Confirmar</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($ag['status'], ['pendente','confirmado'])): ?>
                                    <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <input type="hidden" name="acao" value="cancelar_agendamento">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <input type="hidden" name="redirect" value="agendamentos">
                                        <button class="btn-action secondary" style="padding:4px 10px;font-size:0.8rem;" onclick="return confirm('Cancelar?')">✖ Cancelar</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($ag['status'] === 'confirmado'): ?>
                                    <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                                        <input type="hidden" name="acao" value="concluir_agendamento">
                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                        <input type="hidden" name="redirect" value="agendamentos">
                                        <button class="btn-action" style="padding:4px 10px;font-size:0.8rem;background:#28a745;" onclick="return confirm('Marcar como concluído?')"><i class="fa-solid fa-circle-check"></i> Concluir</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <div style="margin-top:16px;display:flex;gap:6px;flex-wrap:wrap;">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <a href="?acao=agendamentos&pagina=<?php echo $p; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo $filtro_status; ?>"
                       class="btn-action <?php echo $p === $pagina ? '' : 'secondary'; ?>" style="padding:6px 12px;">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php /* ===== NOVO AGENDAMENTO ===== */ elseif ($acao === 'novo_agendamento'): ?>
            <?php
            $lista_clientes_json = [];
            $stmt = $conexao_db->prepare("SELECT id, nome FROM clientes WHERE tipo = 'cliente' AND ativo=1 ORDER BY nome");
            $stmt->execute();
            while ($row = $stmt->get_result()->fetch_assoc()) { $lista_clientes_json[] = $row; }
            $stmt->close();

            $stmt = $conexao_db->prepare("SELECT m.id, m.nome, e.nome AS especialidade FROM medicos m LEFT JOIN especialidades e ON e.id=m.id_especialidade WHERE m.ativo=1 ORDER BY m.nome");
            $stmt->execute(); $lista_medicos = $stmt->get_result()->fetch_all() ?? []; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT id, nome, preco FROM exames WHERE ativo=1 ORDER BY nome");
            $stmt->execute(); $lista_exames = $stmt->get_result()->fetch_all() ?? []; $stmt->close();
            ?>

            <h2>Novo Agendamento</h2>

            <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="max-width:560px;" id="form-agendamento">
                <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                <input type="hidden" name="acao" value="criar_agendamento">

                <div class="form-grupo">
                    <div class="form-grupo-titulo"><i class="fa-solid fa-user"></i> Paciente</div>
                    <label>Nome do Paciente *</label>
                    <input type="text" id="busca-paciente" list="datalist-pacientes"
                           placeholder="Digite o nome do paciente..."
                           autocomplete="off" required
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:4px;box-sizing:border-box;">
                    <datalist id="datalist-pacientes">
                        <?php foreach ($lista_clientes_json as $cl): ?>
                            <option value="<?php echo htmlspecialchars($cl['nome']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="id_cliente" id="id-cliente-hidden">
                    <div id="aviso-paciente" style="display:none;margin-top:6px;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="color:#e74c3c;font-size:0.82rem;"><i class="fa-solid fa-circle-exclamation"></i> Paciente não encontrado no cadastro.</span>
                        <button type="button" onclick="abrirModalCadastro()" style="background:#0f7675;color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:0.8rem;cursor:pointer;">+ Cadastrar novo</button>
                    </div>
                </div>

                <div class="form-grupo">
                    <div class="form-grupo-titulo"><i class="fa-solid fa-calendar-days"></i> Agendamento</div>

                    <label>Tipo *</label>
                    <select name="tipo" id="tipo-select" required onchange="toggleTipo(this.value)"
                            style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                        <option value="consulta">Consulta</option>
                        <option value="exame">Exame</option>
                    </select>

                    <div id="secao-consulta">
                        <label>Médico *</label>
                        <select name="id_medico" id="select-medico" required
                                style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                            <option value="">-- Selecione o médico --</option>
                            <?php foreach ($lista_medicos as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nome']); ?> — <?php echo htmlspecialchars($m['especialidade'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="secao-exame" style="display:none;">
                        <label>Exame *</label>
                        <select name="id_exame" id="select-exame"
                                style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                            <option value="">-- Selecione o exame --</option>
                            <?php foreach ($lista_exames as $ex): ?>
                                <option value="<?php echo $ex['id']; ?>"><?php echo htmlspecialchars($ex['nome']); ?> — R$ <?php echo number_format($ex['preco'], 2, ',', '.'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label>Data e Hora *</label>
                    <input type="datetime-local" name="data_hora" required
                           min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">

                    <label>Observações</label>
                    <textarea name="notas" style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;min-height:80px;"></textarea>
                </div>

                <button type="submit" class="btn-action">Agendar</button>
                <a href="?acao=dashboard" class="btn-action secondary">Cancelar</a>
            </form>

            <!-- Modal: Cadastro Rápido de Paciente -->
            <div id="modal-cadastro" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:12px;padding:28px 32px;width:460px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                        <h3 style="margin:0;color:#1a2332;font-size:1.1rem;"><i class="fa-solid fa-user-plus"></i> Cadastrar Novo Paciente</h3>
                        <button type="button" onclick="fecharModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1;">&times;</button>
                    </div>
                    <div id="modal-erros" style="display:none;background:#fdecea;border:1px solid #f44336;border-radius:6px;padding:10px;margin-bottom:14px;font-size:0.84rem;color:#c62828;"></div>

                    <label style="font-size:0.85rem;font-weight:600;color:#4a5660;display:block;margin-bottom:4px;">Nome Completo *</label>
                    <input type="text" id="m-nome" placeholder="Nome completo do paciente"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:12px;box-sizing:border-box;font-size:0.9rem;">

                    <label style="font-size:0.85rem;font-weight:600;color:#4a5660;display:block;margin-bottom:4px;">Data de Nascimento *</label>
                    <input type="date" id="m-nascimento"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:12px;box-sizing:border-box;font-size:0.9rem;">

                    <label style="font-size:0.85rem;font-weight:600;color:#4a5660;display:block;margin-bottom:4px;">CPF *</label>
                    <input type="text" id="m-cpf" placeholder="000.000.000-00" maxlength="14" oninput="mascaraCPF(this)"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:12px;box-sizing:border-box;font-size:0.9rem;">

                    <label style="font-size:0.85rem;font-weight:600;color:#4a5660;display:block;margin-bottom:4px;">Telefone / WhatsApp *</label>
                    <input type="text" id="m-telefone" placeholder="(00) 00000-0000" maxlength="15" oninput="mascaraTel(this)"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:12px;box-sizing:border-box;font-size:0.9rem;">

                    <label style="font-size:0.85rem;font-weight:600;color:#4a5660;display:block;margin-bottom:4px;">E-mail <span style="font-weight:400;color:#999;">(opcional)</span></label>
                    <input type="email" id="m-email" placeholder="email@exemplo.com"
                           style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:18px;box-sizing:border-box;font-size:0.9rem;">

                    <div style="display:flex;gap:10px;">
                        <button type="button" id="btn-salvar-modal" onclick="salvarPaciente()"
                                style="flex:1;background:#0f7675;color:#fff;border:none;border-radius:8px;padding:11px;font-size:0.95rem;font-weight:600;cursor:pointer;">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar e Continuar
                        </button>
                        <button type="button" onclick="fecharModal()"
                                style="flex:1;background:#f4f6f8;color:#4a5660;border:1px solid #dde1e4;border-radius:8px;padding:11px;font-size:0.95rem;cursor:pointer;">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>

            <script>
            const _pacientes  = <?php echo json_encode($lista_clientes_json); ?>;
            const _csrfToken  = '<?php echo htmlspecialchars(gerar_token_csrf()); ?>';

            document.getElementById('busca-paciente').addEventListener('input', function() {
                const val   = this.value.trim().toLowerCase();
                const match = _pacientes.find(c => c.nome.toLowerCase() === val);
                const aviso = document.getElementById('aviso-paciente');
                const hidden = document.getElementById('id-cliente-hidden');
                if (match) {
                    hidden.value = match.id;
                    aviso.style.display = 'none';
                } else {
                    hidden.value = '';
                    aviso.style.display = val.length > 0 ? 'flex' : 'none';
                }
            });

            function toggleTipo(val) {
                const secConsulta = document.getElementById('secao-consulta');
                const secExame    = document.getElementById('secao-exame');
                const selMedico   = document.getElementById('select-medico');
                const selExame    = document.getElementById('select-exame');
                if (val === 'consulta') {
                    secConsulta.style.display = '';
                    secExame.style.display    = 'none';
                    selMedico.required = true;
                    selExame.required  = false;
                    selExame.value     = '';
                } else {
                    secConsulta.style.display = 'none';
                    secExame.style.display    = '';
                    selMedico.required = false;
                    selExame.required  = true;
                    selMedico.value    = '';
                }
            }

            function abrirModalCadastro() {
                document.getElementById('m-nome').value      = document.getElementById('busca-paciente').value.trim();
                document.getElementById('m-nascimento').value = '';
                document.getElementById('m-cpf').value       = '';
                document.getElementById('m-telefone').value  = '';
                document.getElementById('m-email').value     = '';
                document.getElementById('modal-erros').style.display = 'none';
                document.getElementById('modal-cadastro').style.display = 'flex';
                document.getElementById('m-nome').focus();
            }

            function fecharModal() {
                document.getElementById('modal-cadastro').style.display = 'none';
            }

            async function salvarPaciente() {
                const btn = document.getElementById('btn-salvar-modal');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';

                const fd = new FormData();
                fd.append('csrf_token',      _csrfToken);
                fd.append('nome',            document.getElementById('m-nome').value.trim());
                fd.append('data_nascimento', document.getElementById('m-nascimento').value);
                fd.append('cpf',             document.getElementById('m-cpf').value.replace(/\D/g, ''));
                fd.append('telefone',        document.getElementById('m-telefone').value.replace(/\D/g, ''));
                fd.append('email',           document.getElementById('m-email').value.trim());

                try {
                    const resp = await fetch('../../backend/controllers/cadastrar_paciente_rapido.php', {
                        method: 'POST', body: fd
                    });
                    const data = await resp.json();

                    if (data.sucesso) {
                        _pacientes.push({ id: data.id, nome: data.nome });
                        const opt = document.createElement('option');
                        opt.value = data.nome;
                        document.getElementById('datalist-pacientes').appendChild(opt);
                        document.getElementById('busca-paciente').value       = data.nome;
                        document.getElementById('id-cliente-hidden').value    = data.id;
                        document.getElementById('aviso-paciente').style.display = 'none';
                        fecharModal();
                    } else {
                        const el = document.getElementById('modal-erros');
                        el.innerHTML = (data.erros || ['Erro desconhecido.']).join('<br>');
                        el.style.display = 'block';
                    }
                } catch (e) {
                    const el = document.getElementById('modal-erros');
                    el.textContent = 'Erro de conexão. Tente novamente.';
                    el.style.display = 'block';
                }

                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar e Continuar';
            }

            function mascaraCPF(el) {
                let v = el.value.replace(/\D/g, '').slice(0, 11);
                if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
                else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                else if (v.length > 3) v = v.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                el.value = v;
            }

            function mascaraTel(el) {
                let v = el.value.replace(/\D/g, '').slice(0, 11);
                if (v.length > 10)     v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                else if (v.length > 6) v = v.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
                else if (v.length > 2) v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
                el.value = v;
            }

            document.getElementById('modal-cadastro').addEventListener('click', function(e) {
                if (e.target === this) fecharModal();
            });
            </script>

        <?php /* ===== PACIENTES ===== */ elseif ($acao === 'clientes'): ?>
            <?php
            $busca = sanitizar_input($_GET['busca'] ?? '');
            $where = "tipo = 'cliente'";
            $params = []; $tipos = '';
            if (!empty($busca)) {
                $where .= " AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ?)";
                $params = ["%$busca%","%$busca%","%$busca%"]; $tipos = 'sss';
            }
            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM clientes WHERE $where");
            if (!empty($params)) $stmt->bind_param($tipos, ...$params);
            $stmt->execute(); $total = $stmt->get_result()->fetch_assoc()['t']; $stmt->close();
            $total_paginas = max(1, ceil($total / $por_pagina));

            $stmt = $conexao_db->prepare("SELECT id,nome,email,telefone,data_nascimento,ativo FROM clientes WHERE $where ORDER BY nome LIMIT $por_pagina OFFSET $offset");
            if (!empty($params)) $stmt->bind_param($tipos, ...$params);
            $stmt->execute(); $clientes = $stmt->get_result()->fetch_all() ?? []; $stmt->close();
            ?>

            <h2>Pacientes</h2>

            <form method="GET" style="display:flex;gap:10px;margin-bottom:16px;">
                <input type="hidden" name="acao" value="clientes">
                <input type="text" name="busca" placeholder="Nome, e-mail ou CPF..." value="<?php echo htmlspecialchars($busca); ?>"
                       style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;flex:1;">
                <button class="btn-action" type="submit">Buscar</button>
                <a href="?acao=clientes" class="btn-action secondary">Limpar</a>
            </form>

            <div class="table-responsive">
                <table class="tabela-admin">
                    <thead><tr><th>#</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Nascimento</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr><td colspan="6" style="text-align:center;color:#999;">Nenhum paciente encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cl): ?>
                            <tr>
                                <td><?php echo $cl['id']; ?></td>
                                <td><?php echo htmlspecialchars($cl['nome']); ?></td>
                                <td><?php echo htmlspecialchars($cl['email']); ?></td>
                                <td><?php echo htmlspecialchars($cl['telefone'] ?? '—'); ?></td>
                                <td><?php echo $cl['data_nascimento'] ? formatar_data($cl['data_nascimento']) : '—'; ?></td>
                                <td><span class="badge <?php echo $cl['ativo'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $cl['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
            <div style="margin-top:16px;display:flex;gap:6px;flex-wrap:wrap;">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <a href="?acao=clientes&pagina=<?php echo $p; ?>&busca=<?php echo urlencode($busca); ?>"
                       class="btn-action <?php echo $p === $pagina ? '' : 'secondary'; ?>" style="padding:6px 12px;"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php /* ===== MÉDICOS ===== */ elseif ($acao === 'medicos'): ?>
            <?php
            $stmt = $conexao_db->prepare("SELECT m.*, e.nome AS especialidade FROM medicos m LEFT JOIN especialidades e ON e.id=m.id_especialidade WHERE m.ativo=1 ORDER BY m.nome");
            $stmt->execute(); $medicos = $stmt->get_result()->fetch_all() ?? []; $stmt->close();
            ?>

            <h2>Médicos</h2>
            <div class="table-responsive">
                <table class="tabela-admin">
                    <thead><tr><th>Nome</th><th>CRM</th><th>Especialidade</th><th>Email</th><th>Telefone</th></tr></thead>
                    <tbody>
                        <?php foreach ($medicos as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['nome']); ?></td>
                            <td><?php echo htmlspecialchars($m['crm']); ?></td>
                            <td><?php echo htmlspecialchars($m['especialidade'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($m['email'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($m['telefone'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        </div><!-- /.painel-main -->
    </main>
</div>

<script>
(function(){
    var sidebar = document.getElementById('sidebar');
    var btn     = document.getElementById('sidebarToggle');
    var icon    = document.getElementById('toggleIcon');
    var key     = 'sidebar_recep_collapsed';

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
