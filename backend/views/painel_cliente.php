<?php
// ====================================================
// ARQUIVO: backend/views/painel_cliente.php
// Descrição: Painel principal do cliente logado
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Exige usuário autenticado e do tipo 'cliente'; outros tipos são redirecionados ao painel correto
require_cliente();

// Variáveis
$conexao_db = Conexao::getInstance()->getConexao();
$id_cliente = $_SESSION['id_cliente'];
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');

// Obter dados do cliente
$stmt = $conexao_db->prepare('SELECT * FROM clientes WHERE id = ? AND tipo = "cliente"');
$stmt->bind_param('i', $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    // Sessão inconsistente: tipo_usuario diz 'cliente' mas o registro não existe ou tem tipo diferente
    session_destroy();
    redirect('cadastro/login.php');
}

$base_url = '../../';
$titulo_pagina = 'Meu Painel - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

    <?php
        $flash_agendamento = get_flash_message('agendamento');
        $flash_login       = get_flash_message('login');
        $flash_perfil      = get_flash_message('perfil');
    ?>
    <?php if ($flash_login || $flash_agendamento || $flash_perfil || !empty($_SESSION['erros_agendamento']) || !empty($_SESSION['erros_perfil'])): ?>
        <div class="flash-container" role="status" aria-live="polite">
            <?php if ($flash_login): ?>
                <div class="flash-toast flash-sucesso">
                    <span class="flash-toast-icone"><i class="fa-solid fa-circle-check"></i></span>
                    <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_login['mensagem']); ?></span>
                    <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                    <span class="flash-toast-progresso"></span>
                </div>
            <?php endif; ?>
            <?php if ($flash_agendamento): ?>
                <div class="flash-toast <?php echo $flash_agendamento['tipo'] === 'sucesso' ? 'flash-sucesso' : 'flash-erro'; ?>">
                    <span class="flash-toast-icone"><?php echo $flash_agendamento['tipo'] === 'sucesso' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>'; ?></span>
                    <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_agendamento['mensagem']); ?></span>
                    <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                    <span class="flash-toast-progresso"></span>
                </div>
            <?php endif; ?>
            <?php if ($flash_perfil): ?>
                <div class="flash-toast <?php echo $flash_perfil['tipo'] === 'sucesso' ? 'flash-sucesso' : 'flash-erro'; ?>">
                    <span class="flash-toast-icone"><?php echo $flash_perfil['tipo'] === 'sucesso' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>'; ?></span>
                    <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_perfil['mensagem']); ?></span>
                    <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                    <span class="flash-toast-progresso"></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['erros_agendamento'])): ?>
                <?php foreach ($_SESSION['erros_agendamento'] as $erro): ?>
                    <div class="flash-toast flash-erro">
                        <span class="flash-toast-icone"><i class="fa-solid fa-circle-xmark"></i></span>
                        <span class="flash-toast-texto"><?php echo htmlspecialchars($erro); ?></span>
                        <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                        <span class="flash-toast-progresso"></span>
                    </div>
                <?php endforeach; ?>
                <?php unset($_SESSION['erros_agendamento']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['erros_perfil'])): ?>
                <?php foreach ($_SESSION['erros_perfil'] as $erro): ?>
                    <div class="flash-toast flash-erro">
                        <span class="flash-toast-icone"><i class="fa-solid fa-circle-xmark"></i></span>
                        <span class="flash-toast-texto"><?php echo htmlspecialchars($erro); ?></span>
                        <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                        <span class="flash-toast-progresso"></span>
                    </div>
                <?php endforeach; ?>
                <?php unset($_SESSION['erros_perfil']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- MODAL DE CONFIRMAÇÃO (cancelamento de agendamento) -->
    <div id="modal-confirmacao" class="modal-overlay">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-confirmacao-titulo">
            <div class="modal-icone"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3 data-modal-titulo id="modal-confirmacao-titulo">Confirmar ação</h3>
            <p data-modal-texto>Tem certeza?</p>
            <div class="modal-acoes">
                <button type="button" data-modal-cancelar class="btn-action secondary">Não, voltar</button>
                <button type="button" data-modal-confirmar class="btn-action" style="background: #dc3545;">Sim, cancelar</button>
            </div>
        </div>
    </div>

<?php
$titulos_cliente = [
    'dashboard'    => 'Dashboard',
    'perfil'       => 'Meu Perfil',
    'editar_perfil'=> 'Meu Perfil',
    'alterar_senha'=> 'Meu Perfil',
    'agendamentos' => 'Agendamentos',
    'agendar'      => 'Novo Agendamento',
    'exames'       => 'Solicitar Exame',
];
$titulo_acao_cliente = $titulos_cliente[$acao] ?? 'Dashboard';
$inicial_cliente = mb_strtoupper(mb_substr($cliente['nome'] ?? 'C', 0, 1));
?>

<div class="painel-wrapper">

    <!-- Sidebar Cliente -->
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
                <i class="fa-solid fa-chart-bar"></i><span>Dashboard</span>
            </a>
            <a href="?acao=perfil" class="sidebar-item <?php echo in_array($acao, ['perfil', 'editar_perfil', 'alterar_senha']) ? 'ativo' : ''; ?>" data-tooltip="Meu Perfil">
                <i class="fa-solid fa-user"></i><span>Meu Perfil</span>
            </a>
            <a href="?acao=agendamentos" class="sidebar-item <?php echo $acao === 'agendamentos' ? 'ativo' : ''; ?>" data-tooltip="Agendamentos">
                <i class="fa-solid fa-calendar-days"></i><span>Agendamentos</span>
            </a>
            <a href="?acao=agendar" class="sidebar-item <?php echo $acao === 'agendar' ? 'ativo' : ''; ?>" data-tooltip="Agendar Consulta">
                <i class="fa-solid fa-plus"></i><span>Agendar Consulta</span>
            </a>
            <a href="?acao=exames" class="sidebar-item <?php echo $acao === 'exames' ? 'ativo' : ''; ?>" data-tooltip="Solicitar Exame">
                <i class="fa-solid fa-dna"></i><span>Solicitar Exame</span>
            </a>
            <?php if (is_admin()): ?>
                <a href="painel_admin.php" class="sidebar-item" data-tooltip="Painel Admin">
                    <i class="fa-solid fa-gear"></i><span>Painel Admin</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="../auth/deslogar.php" class="sidebar-item sidebar-sair" data-tooltip="Sair">
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
                <span class="breadcrumb-root">Meu Painel</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($titulo_acao_cliente); ?></span>
            </span>
        </div>
        <div class="painel-main">
            <?php if ($acao === 'dashboard'): ?>
                <!-- DASHBOARD -->
                <h2>🏠 Bem-vindo, <?php echo htmlspecialchars($cliente['nome']); ?>!</h2>

                <div class="info-card">
                    <span class="info-card-icone" data-tooltip="Quando seus dados de perfil foram atualizados pela última vez">🕒</span>
                    <div class="info-card-corpo">
                        <span class="info-card-label">Última atualização de perfil</span>
                        <span class="info-card-valor"><?php echo formatar_data_hora($cliente['data_atualizacao']); ?></span>
                    </div>
                </div>

                <h3 style="color: #007b83; margin-top: 20px;">Seus próximos agendamentos</h3>

                <?php
                    $stmt = $conexao_db->prepare(
                        'SELECT a.*, COALESCE(e.nome, sp.nome, a.notas, "-") as nome_item, a.tipo, m.nome as nome_medico
                         FROM agendamentos a
                         LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                         LEFT JOIN exames e ON a.id_exame = e.id
                         LEFT JOIN medicos m ON a.id_medico = m.id
                         WHERE a.id_cliente = ? AND a.status != "cancelado" AND a.data_hora > datetime("now", "localtime")
                         ORDER BY a.data_hora ASC
                         LIMIT 5'
                    );
                    $stmt->bind_param('i', $id_cliente);
                    $stmt->execute();
                    $agendamentos = $stmt->get_result();
                    $stmt->close();

                    if ($agendamentos->num_rows === 0):
                ?>
                    <p style="margin-top: 15px; color: #666;">Nenhum agendamento futuro.</p>
                    <a href="?acao=agendar" class="btn-action">Agendar agora</a>
                <?php else: ?>
                    <?php $lista_proximos = []; while ($ag = $agendamentos->fetch_assoc()) { $lista_proximos[] = $ag; } ?>
                    <div class="agendamentos-tabela-wrap">
                        <table style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Item</th>
                                    <th>Médico</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_proximos as $ag): ?>
                                    <tr>
                                        <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                                        <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($ag['nome_item'] ?? ''); ?></td>
                                        <td><?php echo !empty($ag['nome_medico']) ? htmlspecialchars($ag['nome_medico']) : '-'; ?></td>
                                        <td><?php echo !empty($ag['valor_total']) ? formatar_valor($ag['valor_total']) : '-'; ?></td>
                                        <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="agendamentos-grid">
                        <?php foreach ($lista_proximos as $ag): ?>
                            <div class="agendamento-card">
                                <div class="agendamento-card-cabecalho">
                                    <div class="agendamento-card-icone">
                                        <?php echo $ag['tipo'] === 'exame' ? obter_icone_exame($ag['nome_item'] ?? '') : obter_icone_especialidade($ag['nome_item'] ?? ''); ?>
                                    </div>
                                    <div>
                                        <div class="agendamento-card-titulo"><?php echo htmlspecialchars($ag['nome_item'] ?? ''); ?></div>
                                        <div class="agendamento-card-tipo"><?php echo get_tipo_agendamento($ag['tipo']); ?></div>
                                    </div>
                                </div>
                                <div class="agendamento-card-data">🗓️ <?php echo formatar_data_hora($ag['data_hora']); ?></div>
                                <?php if (!empty($ag['nome_medico'])): ?>
                                    <div class="agendamento-card-data"><i class="fa-solid fa-stethoscope"></i> Dr(a). <?php echo htmlspecialchars($ag['nome_medico']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($ag['valor_total'])): ?>
                                    <div class="agendamento-card-data">💰 <?php echo formatar_valor($ag['valor_total']); ?></div>
                                <?php endif; ?>
                                <div class="agendamento-card-rodape">
                                    <span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($acao === 'perfil'): ?>
                <!-- PERFIL -->
                <h2><i class="fa-solid fa-user"></i> Meu Perfil</h2>

                <div class="info-grid">
                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="Seu nome completo de cadastro"><i class="fa-solid fa-user"></i></span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">Nome</span>
                            <span class="info-card-valor"><?php echo htmlspecialchars($cliente['nome']); ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="E-mail usado para login e notificações">📧</span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">Email</span>
                            <span class="info-card-valor"><?php echo htmlspecialchars($cliente['email']); ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="Documento utilizado para identificação">🪪</span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">CPF</span>
                            <span class="info-card-valor"><?php echo formatar_cpf($cliente['cpf']); ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="Telefone para contato em caso de necessidade"><i class="fa-solid fa-mobile-screen"></i></span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">Telefone</span>
                            <span class="info-card-valor"><?php echo !empty($cliente['telefone']) ? formatar_telefone($cliente['telefone']) : '-'; ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="Usada para calcular sua idade">🎂</span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">Data de Nascimento</span>
                            <span class="info-card-valor"><?php echo !empty($cliente['data_nascimento']) ? formatar_data($cliente['data_nascimento']) . ' (' . calcular_idade($cliente['data_nascimento']) . ' anos)' : '-'; ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card-icone" data-tooltip="Data em que você se cadastrou na clínica"><i class="fa-solid fa-calendar-days"></i></span>
                        <div class="info-card-corpo">
                            <span class="info-card-label">Membro desde</span>
                            <span class="info-card-valor"><?php echo formatar_data($cliente['data_cadastro']); ?></span>
                        </div>
                    </div>
                </div>

                <p style="margin-top: 20px;">
                    <a href="?acao=editar_perfil" class="btn-action">Editar Perfil</a>
                    <a href="?acao=alterar_senha" class="btn-action secondary">Alterar Senha</a>
                </p>

            <?php elseif ($acao === 'agendamentos'): ?>
                <!-- AGENDAMENTOS -->
                <h2><i class="fa-solid fa-calendar-days"></i> Meus Agendamentos</h2>

                <?php
                    $filtro_status = sanitizar_input($_GET['status'] ?? '');
                    $filtro_tipo   = sanitizar_input($_GET['tipo']   ?? '');
                    $status_validos = ['pendente','confirmado','cancelado','concluído'];
                    $tipos_validos  = ['consulta','exame'];
                    if (!in_array($filtro_status, $status_validos, true)) $filtro_status = '';
                    if (!in_array($filtro_tipo, $tipos_validos, true))     $filtro_tipo   = '';

                    $where_extra = '';
                    $params_extra = [$id_cliente];
                    $tipos_extra  = 'i';
                    if ($filtro_status !== '') { $where_extra .= ' AND a.status = ?';  $params_extra[] = $filtro_status; $tipos_extra .= 's'; }
                    if ($filtro_tipo   !== '') { $where_extra .= ' AND a.tipo = ?';    $params_extra[] = $filtro_tipo;   $tipos_extra .= 's'; }

                    $stmt = $conexao_db->prepare(
                        "SELECT a.*, COALESCE(e.nome, sp.nome, a.notas, '-') as nome_item, a.tipo, m.nome as nome_medico
                         FROM agendamentos a
                         LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                         LEFT JOIN exames e ON a.id_exame = e.id
                         LEFT JOIN medicos m ON a.id_medico = m.id
                         WHERE a.id_cliente = ? $where_extra
                         ORDER BY a.data_hora DESC"
                    );
                    $stmt->bind_param($tipos_extra, ...$params_extra);
                    $stmt->execute();
                    $agendamentos = $stmt->get_result();
                    $stmt->close();

                    // Converter para array para reutilizar
                    $lista_agendamentos = [];
                    while ($row = $agendamentos->fetch_assoc()) $lista_agendamentos[] = $row;
                ?>

                <!-- Filtros -->
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
                    <input type="hidden" name="acao" value="agendamentos">
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;">Status</label>
                        <select name="status" style="padding:8px;border:1px solid #e0e0e0;border-radius:5px;">
                            <option value="">Todos</option>
                            <?php foreach (['pendente'=>'Pendente','confirmado'=>'Confirmado','cancelado'=>'Cancelado','concluído'=>'Concluído'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $filtro_status === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;margin-bottom:4px;">Tipo</label>
                        <select name="tipo" style="padding:8px;border:1px solid #e0e0e0;border-radius:5px;">
                            <option value="">Todos</option>
                            <option value="consulta" <?php echo $filtro_tipo === 'consulta' ? 'selected' : ''; ?>>Consulta</option>
                            <option value="exame"    <?php echo $filtro_tipo === 'exame'    ? 'selected' : ''; ?>>Exame</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn-action">Filtrar</button>
                        <?php if ($filtro_status || $filtro_tipo): ?>
                            <a href="?acao=agendamentos" class="btn-action secondary">Limpar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($lista_agendamentos)):
                ?>
                    <p>Nenhum agendamento<?php echo ($filtro_status || $filtro_tipo) ? ' com os filtros selecionados' : ''; ?>.</p>
                    <a href="?acao=agendar" class="btn-action">Criar agendamento</a>
                <?php else: ?>
                    <div class="agendamentos-tabela-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Item</th>
                                    <th>Médico</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_agendamentos as $ag): ?>
                                    <tr>
                                        <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                                        <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($ag['nome_item'] ?? ''); ?></td>
                                        <td><?php echo !empty($ag['nome_medico']) ? htmlspecialchars($ag['nome_medico']) : '-'; ?></td>
                                        <td><?php echo !empty($ag['valor_total']) ? formatar_valor($ag['valor_total']) : '-'; ?></td>
                                        <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                        <td style="white-space:nowrap;">
                                            <?php if ($ag['tipo'] === 'consulta' && pode_cancelar_agendamento($ag['status']) && is_agendamento_futuro($ag['data_hora'])): ?>
                                                <a href="?acao=editar_agendamento&id=<?php echo $ag['id']; ?>" class="btn-action" style="margin-bottom:4px;">Reagendar</a>
                                            <?php endif; ?>
                                            <?php if (pode_cancelar_agendamento($ag['status'])): ?>
                                                <a href="?acao=cancelar_agendamento&id=<?php echo $ag['id']; ?>" class="btn-action secondary" data-confirm="Deseja cancelar? Cancelamentos exigem 2h de antecedência." data-confirm-titulo="Cancelar agendamento">Cancelar</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="agendamentos-grid">
                        <?php foreach ($lista_agendamentos as $ag): ?>
                            <div class="agendamento-card">
                                <div class="agendamento-card-cabecalho">
                                    <div class="agendamento-card-icone">
                                        <?php echo $ag['tipo'] === 'exame' ? obter_icone_exame($ag['nome_item'] ?? '') : obter_icone_especialidade($ag['nome_item'] ?? ''); ?>
                                    </div>
                                    <div>
                                        <div class="agendamento-card-titulo"><?php echo htmlspecialchars($ag['nome_item'] ?? ''); ?></div>
                                        <div class="agendamento-card-tipo"><?php echo get_tipo_agendamento($ag['tipo']); ?></div>
                                    </div>
                                </div>
                                <div class="agendamento-card-data">🗓️ <?php echo formatar_data_hora($ag['data_hora']); ?></div>
                                <?php if (!empty($ag['nome_medico'])): ?>
                                    <div class="agendamento-card-data"><i class="fa-solid fa-stethoscope"></i> Dr(a). <?php echo htmlspecialchars($ag['nome_medico']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($ag['valor_total'])): ?>
                                    <div class="agendamento-card-data">💰 <?php echo formatar_valor($ag['valor_total']); ?></div>
                                <?php endif; ?>
                                <div class="agendamento-card-rodape">
                                    <span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span>
                                    <?php if (pode_cancelar_agendamento($ag['status'])): ?>
                                        <a href="?acao=cancelar_agendamento&id=<?php echo $ag['id']; ?>" class="btn-action secondary" data-confirm="Deseja realmente cancelar este agendamento?" data-confirm-titulo="Cancelar agendamento">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($acao === 'agendar'): ?>
                <!-- AGENDAR CONSULTA -->
                <h2><i class="fa-solid fa-plus"></i> Agendar Consulta</h2>

                <?php
                    // Pré-seleção via ?id_medico= (vindo de medico_detalhe.php)
                    $pre_id_medico = intval($_GET['id_medico'] ?? 0);
                    $pre_id_especialidade = 0;
                    if ($pre_id_medico > 0) {
                        $stmt_pre = $conexao_db->prepare('SELECT id_especialidade FROM medicos WHERE id=? AND ativo=1');
                        $stmt_pre->bind_param('i', $pre_id_medico);
                        $stmt_pre->execute();
                        $row_pre = $stmt_pre->get_result()->fetch_assoc();
                        $stmt_pre->close();
                        $pre_id_especialidade = (int) ($row_pre['id_especialidade'] ?? 0);
                    }
                ?>

                <form id="form-agendar" method="POST" action="../controllers/agendamento_controller.php"
                      style="max-width: 500px;"
                      data-pre-especialidade="<?php echo $pre_id_especialidade; ?>"
                      data-pre-medico="<?php echo $pre_id_medico; ?>"
                      data-pre-data="" data-pre-horario="">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-stethoscope"></i> Dados da consulta</div>

                        <div style="margin-bottom: 15px;">
                            <label for="especialidade"><strong>Especialidade *</strong></label>
                            <select id="especialidade" name="id_especialidade" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                                <option value="">-- Selecione uma especialidade --</option>
                                <?php
                                    $stmt = $conexao_db->prepare('SELECT id, nome FROM especialidades WHERE ativo = 1 ORDER BY nome');
                                    $stmt->execute();
                                    $especialidades = $stmt->get_result();
                                    while ($esp = $especialidades->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $esp['id']; ?>"><?php echo htmlspecialchars($esp['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="medico"><strong>Médico *</strong></label>
                            <select id="medico" name="id_medico" required disabled style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                                <option value="">-- Selecione uma especialidade primeiro --</option>
                            </select>
                            <small id="valor-consulta-info" style="display: block; margin-top: 5px; color: var(--cor-texto-claro);"></small>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="data_consulta"><strong>Data *</strong></label>
                            <input type="date" id="data_consulta" name="data" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="horario"><strong>Horário *</strong></label>
                            <select id="horario" name="horario" required disabled style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                                <option value="">-- Selecione o médico e a data --</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 0;">
                            <label for="notas"><strong>Observações</strong></label>
                            <textarea id="notas" name="notas" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="acao" value="agendar_consulta">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <button type="submit" class="btn-action">Agendar</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Voltar</a>
                </form>

            <?php elseif ($acao === 'exames'): ?>
                <!-- SOLICITAR EXAME -->
                <h2><i class="fa-solid fa-dna"></i> Solicitar Exame</h2>

                <form method="POST" action="../controllers/agendamento_controller.php" style="max-width: 500px;">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-dna"></i> Dados do exame</div>

                        <div style="margin-bottom: 15px;">
                            <label for="exame"><strong>Exame *</strong></label>
                            <select id="exame" name="id_exame" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                                <option value="">-- Selecione um exame --</option>
                                <?php
                                    $stmt = $conexao_db->prepare('SELECT id, nome, preco FROM exames WHERE ativo = 1 ORDER BY nome');
                                    $stmt->execute();
                                    $exames_lista = $stmt->get_result();
                                    while ($ex = $exames_lista->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $ex['id']; ?>"><?php echo htmlspecialchars($ex['nome']) . ' - ' . formatar_valor($ex['preco']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="data_exame"><strong>Data e Hora *</strong></label>
                            <input type="datetime-local" id="data_exame" name="data_hora" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        </div>

                        <div style="margin-bottom: 0;">
                            <label for="notas_exame"><strong>Observações</strong></label>
                            <textarea id="notas_exame" name="notas" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="acao" value="agendar_exame">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <button type="submit" class="btn-action">Solicitar Exame</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Voltar</a>
                </form>

            <?php elseif ($acao === 'editar_agendamento'): ?>
                <!-- REAGENDAR CONSULTA -->
                <?php
                    $id_ag_editar = intval($_GET['id'] ?? 0);
                    $stmt_ed = $conexao_db->prepare(
                        'SELECT a.*, e.nome as nome_esp FROM agendamentos a
                         LEFT JOIN especialidades e ON a.id_especialidade = e.id
                         WHERE a.id = ? AND a.id_cliente = ? AND a.tipo = "consulta"'
                    );
                    $stmt_ed->bind_param('ii', $id_ag_editar, $id_cliente);
                    $stmt_ed->execute();
                    $ag_editar = $stmt_ed->get_result()->fetch_assoc();
                    $stmt_ed->close();

                    if (!$ag_editar || !pode_cancelar_agendamento($ag_editar['status'])):
                ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-xmark"></i> Agendamento não encontrado ou não pode ser reagendado.
                    </div>
                    <a href="?acao=agendamentos" class="btn-action secondary">Voltar</a>
                <?php else:
                    $pre_data_editar    = substr($ag_editar['data_hora'], 0, 10);
                    $pre_horario_editar = substr($ag_editar['data_hora'], 11, 5);
                ?>
                <h2><i class="fa-solid fa-calendar-pen"></i> Reagendar Consulta</h2>

                <form id="form-agendar" method="POST" action="../controllers/agendamento_controller.php"
                      style="max-width: 500px;"
                      data-pre-especialidade="<?php echo (int) $ag_editar['id_especialidade']; ?>"
                      data-pre-medico="<?php echo (int) $ag_editar['id_medico']; ?>"
                      data-pre-data="<?php echo htmlspecialchars($pre_data_editar); ?>"
                      data-pre-horario="<?php echo htmlspecialchars($pre_horario_editar); ?>">

                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-stethoscope"></i> Novos dados da consulta</div>

                        <div style="margin-bottom: 15px;">
                            <label for="especialidade"><strong>Especialidade *</strong></label>
                            <select id="especialidade" name="id_especialidade" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
                                <option value="">-- Selecione --</option>
                                <?php
                                    $stmt_esp = $conexao_db->prepare('SELECT id, nome FROM especialidades WHERE ativo=1 ORDER BY nome');
                                    $stmt_esp->execute();
                                    while ($esp = $stmt_esp->get_result()->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $esp['id']; ?>"><?php echo htmlspecialchars($esp['nome']); ?></option>
                                <?php endwhile; $stmt_esp->close(); ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="medico"><strong>Médico *</strong></label>
                            <select id="medico" name="id_medico" required disabled style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
                                <option value="">-- Selecione uma especialidade primeiro --</option>
                            </select>
                            <small id="valor-consulta-info" style="display:block;margin-top:5px;color:var(--cor-texto-claro);"></small>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="data_consulta"><strong>Data *</strong></label>
                            <input type="date" id="data_consulta" name="data" required min="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="horario"><strong>Horário *</strong></label>
                            <select id="horario" name="horario" required disabled style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
                                <option value="">-- Selecione o médico e a data --</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 0;">
                            <label for="notas"><strong>Observações</strong></label>
                            <textarea id="notas" name="notas" style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;min-height:80px;"><?php echo htmlspecialchars($ag_editar['notas'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="acao" value="alterar_agendamento">
                    <input type="hidden" name="id_agendamento" value="<?php echo $id_ag_editar; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <button type="submit" class="btn-action">Confirmar Reagendamento</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Cancelar</a>
                </form>
                <?php endif; ?>

            <?php elseif ($acao === 'editar_perfil'): ?>
                <!-- EDITAR PERFIL -->
                <h2><i class="fa-solid fa-user-pen"></i> Editar Perfil</h2>

                <form method="POST" action="../controllers/perfil_controller.php" style="max-width: 500px;">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-user"></i> Dados pessoais</div>

                        <label for="nome">Nome completo *</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>

                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>

                        <label for="telefone">Telefone *</label>
                        <input type="tel" id="telefone" name="telefone" maxlength="15" placeholder="(11) 99999-9999"
                               data-mascara="telefone"
                               value="<?php echo htmlspecialchars(formatar_telefone($cliente['telefone'])); ?>" required>
                    </div>

                    <div class="alert alert-info" style="margin-bottom: 16px;">
                        <i class="fa-solid fa-circle-info"></i>
                        CPF e data de nascimento não podem ser alterados. Em caso de erro, entre em contato com a clínica.
                    </div>

                    <input type="hidden" name="acao" value="atualizar_perfil">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <button type="submit" class="btn-action">Salvar Alterações</button>
                    <a href="?acao=perfil" class="btn-action secondary">Cancelar</a>
                </form>

            <?php elseif ($acao === 'alterar_senha'): ?>
                <!-- ALTERAR SENHA -->
                <h2><i class="fa-solid fa-lock"></i> Alterar Senha</h2>

                <form method="POST" action="../controllers/perfil_controller.php" style="max-width: 500px;">
                    <div class="form-grupo">
                        <div class="form-grupo-titulo"><i class="fa-solid fa-lock"></i> Nova senha</div>

                        <label for="senha_atual">Senha atual *</label>
                        <input type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password">

                        <label for="nova_senha">Nova senha *</label>
                        <input type="password" id="nova_senha" name="nova_senha" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password">
                        <small style="display:block; margin-bottom:12px; color:var(--cor-texto-claro);">Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</small>

                        <label for="confirmar_senha">Confirmar nova senha *</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required autocomplete="new-password">
                    </div>

                    <input type="hidden" name="acao" value="alterar_senha">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <button type="submit" class="btn-action">Alterar Senha</button>
                    <a href="?acao=perfil" class="btn-action secondary">Cancelar</a>
                </form>

            <?php elseif ($acao === 'cancelar_agendamento'): ?>
                <!-- CANCELAR AGENDAMENTO -->
                <?php
                    $id_agendamento = intval($_GET['id'] ?? 0);

                    $stmt = $conexao_db->prepare('SELECT * FROM agendamentos WHERE id = ? AND id_cliente = ?');
                    $stmt->bind_param('ii', $id_agendamento, $id_cliente);
                    $stmt->execute();
                    $agendamento = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$agendamento || !pode_cancelar_agendamento($agendamento['status'])):
                ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-xmark"></i> Agendamento não encontrado ou não pode ser cancelado.
                    </div>
                <?php else: ?>
                    <h2>Cancelar Agendamento</h2>
                    <div class="info-card">
                        <strong>Tem certeza que deseja cancelar este agendamento?</strong>
                    </div>

                    <form method="POST" action="../controllers/agendamento_controller.php">
                        <input type="hidden" name="acao" value="cancelar_agendamento">
                        <input type="hidden" name="id_agendamento" value="<?php echo $id_agendamento; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                        <button type="submit" class="btn-action" style="background: #dc3545;">Sim, cancelar</button>
                        <a href="?acao=agendamentos" class="btn-action secondary">Não, voltar</a>
                    </form>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-xmark"></i> Ação não encontrada.
                </div>
            <?php endif; ?>
        </div><!-- /.painel-main -->
    </main>
</div><!-- /.painel-wrapper -->

<script>
(function(){
    var sidebar = document.getElementById('sidebar');
    var btn     = document.getElementById('sidebarToggle');
    var icon    = document.getElementById('toggleIcon');
    var key     = 'sidebar_cliente_collapsed';

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
