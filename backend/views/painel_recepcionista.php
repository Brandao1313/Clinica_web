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
require_once __DIR__ . '/../../includes/header.php';
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

<div class="painel-container">

    <!-- Sidebar -->
    <aside class="painel-sidebar">
        <div class="avatar-inicial"><?php echo mb_strtoupper(mb_substr($nome_recep, 0, 1)); ?></div>
        <div class="painel-nome"><?php echo htmlspecialchars($nome_recep); ?></div>
        <div class="painel-subtitulo">Recepcionista</div>

        <nav class="painel-nav">
            <a href="?acao=dashboard" class="painel-nav-item <?php echo $acao === 'dashboard' ? 'ativo' : ''; ?>">
                <i class="fa-solid fa-chart-bar"></i> Dashboard
            </a>
            <a href="?acao=agendamentos" class="painel-nav-item <?php echo $acao === 'agendamentos' ? 'ativo' : ''; ?>">
                <i class="fa-solid fa-calendar-days"></i> Agendamentos
            </a>
            <a href="?acao=novo_agendamento" class="painel-nav-item <?php echo $acao === 'novo_agendamento' ? 'ativo' : ''; ?>">
                <i class="fa-solid fa-plus"></i> Novo Agendamento
            </a>
            <a href="?acao=clientes" class="painel-nav-item <?php echo $acao === 'clientes' ? 'ativo' : ''; ?>">
                <i class="fa-solid fa-users"></i> Pacientes
            </a>
            <a href="?acao=medicos" class="painel-nav-item <?php echo $acao === 'medicos' ? 'ativo' : ''; ?>">
                <i class="fa-solid fa-stethoscope"></i> Médicos
            </a>
            <a href="../../backend/auth/deslogar.php" class="painel-nav-item" style="color:#e74c3c;">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="painel-main">

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

            $stmt = $conexao_db->prepare("SELECT COUNT(*) as t FROM clientes WHERE eh_admin = 0 AND eh_recepcionista = 0");
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
            $stmt = $conexao_db->prepare("SELECT id, nome, email FROM clientes WHERE eh_admin=0 AND eh_recepcionista=0 AND ativo=1 ORDER BY nome");
            $stmt->execute(); $lista_clientes = $stmt->get_result()->fetch_all() ?? []; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT m.id, m.nome, e.nome AS especialidade FROM medicos m LEFT JOIN especialidades e ON e.id=m.id_especialidade WHERE m.ativo=1 ORDER BY m.nome");
            $stmt->execute(); $lista_medicos = $stmt->get_result()->fetch_all() ?? []; $stmt->close();

            $stmt = $conexao_db->prepare("SELECT id, nome FROM especialidades WHERE ativo=1 ORDER BY nome");
            $stmt->execute(); $lista_esp = $stmt->get_result()->fetch_all() ?? []; $stmt->close();
            ?>

            <h2>Novo Agendamento</h2>

            <form method="POST" action="../../backend/controllers/recepcionista_controller.php" style="max-width:560px;">
                <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                <input type="hidden" name="acao" value="criar_agendamento">

                <div class="form-grupo">
                    <div class="form-grupo-titulo"><i class="fa-solid fa-user"></i> Paciente</div>
                    <label>Paciente *</label>
                    <select name="id_cliente" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                        <option value="">-- Selecione o paciente --</option>
                        <?php foreach ($lista_clientes as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nome']); ?> (<?php echo htmlspecialchars($cl['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grupo">
                    <div class="form-grupo-titulo"><i class="fa-solid fa-calendar-days"></i> Agendamento</div>
                    <label>Tipo *</label>
                    <select name="tipo" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                        <option value="consulta">Consulta</option>
                        <option value="exame">Exame</option>
                    </select>

                    <label>Médico</label>
                    <select name="id_medico" style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                        <option value="">-- Sem médico específico --</option>
                        <?php foreach ($lista_medicos as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nome']); ?> — <?php echo htmlspecialchars($m['especialidade'] ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Especialidade</label>
                    <select name="id_especialidade" style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:12px;">
                        <option value="">-- Nenhuma --</option>
                        <?php foreach ($lista_esp as $esp): ?>
                            <option value="<?php echo $esp['id']; ?>"><?php echo htmlspecialchars($esp['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>

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

        <?php /* ===== PACIENTES ===== */ elseif ($acao === 'clientes'): ?>
            <?php
            $busca = sanitizar_input($_GET['busca'] ?? '');
            $where = "eh_admin = 0 AND eh_recepcionista = 0";
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

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
