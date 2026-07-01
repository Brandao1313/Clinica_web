<?php
// ====================================================
// ARQUIVO: backend/views/painel_medico.php
// Descrição: Painel do médico logado
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/validacao.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

require_medico();

$conexao_db = Conexao::getInstance()->getConexao();
$id_medico  = $_SESSION['id_medico']  ?? null;
$id_cliente = $_SESSION['id_cliente'] ?? null;
$acao       = sanitizar_input($_GET['acao'] ?? 'dashboard');

// Sessão sem id_medico = login incompleto
if (!$id_medico) {
    session_destroy();
    header('Location: ' . SITE_URL . '/cadastro/login.php');
    exit;
}

// Dados clínicos do médico + e-mail/telefone da conta de login
$stmt = $conexao_db->prepare(
    'SELECT m.*, e.nome AS nome_especialidade,
            c.email AS login_email, c.telefone AS login_telefone
     FROM medicos m
     LEFT JOIN especialidades e ON e.id = m.id_especialidade
     LEFT JOIN clientes c ON c.id = m.id_cliente
     WHERE m.id = ?'
);
$stmt->bind_param('i', $id_medico);
$stmt->execute();
$medico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$medico) {
    session_destroy();
    header('Location: ' . SITE_URL . '/cadastro/login.php');
    exit;
}

$base_url     = '../../';
$titulo_pagina = 'Painel do Médico - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../includes/header.php';
?>

<?php $flash_login = get_flash_message('login'); ?>
<?php if ($flash_login): ?>
    <div class="flash-container" role="status" aria-live="polite">
        <div class="flash-toast flash-sucesso">
            <span class="flash-toast-icone"><i class="fa-solid fa-circle-check"></i></span>
            <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_login['mensagem']); ?></span>
            <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
            <span class="flash-toast-progresso"></span>
        </div>
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
            <a href="?acao=agenda" class="sidebar-item <?php echo $acao === 'agenda' ? 'ativo' : ''; ?>" data-tooltip="Minha Agenda">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Minha Agenda</span>
            </a>
            <a href="?acao=historico" class="sidebar-item <?php echo $acao === 'historico' ? 'ativo' : ''; ?>" data-tooltip="Histórico">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Histórico</span>
            </a>
            <a href="?acao=perfil" class="sidebar-item <?php echo $acao === 'perfil' ? 'ativo' : ''; ?>" data-tooltip="Meu Perfil">
                <i class="fa-solid fa-user"></i>
                <span>Meu Perfil</span>
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
$titulos_medico = [
    'dashboard' => 'Dashboard',
    'agenda'    => 'Minha Agenda',
    'historico' => 'Histórico',
    'perfil'    => 'Meu Perfil',
];
$titulo_acao_medico = $titulos_medico[$acao] ?? 'Dashboard';
?>

    <!-- Conteúdo principal -->
    <main class="sidebar-main">
        <div class="breadcrumb-bar">
            <button class="breadcrumb-toggle" onclick="document.getElementById('sidebarToggle').click()" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="breadcrumb-path">
                <span class="breadcrumb-root">Painel Médico</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($titulo_acao_medico); ?></span>
            </span>
        </div>
        <div class="painel-main">

        <?php if ($acao === 'dashboard'): ?>
            <!-- ===== DASHBOARD ===== -->
            <?php
            // Consultas de hoje
            $stmt = $conexao_db->prepare(
                "SELECT COUNT(*) as total FROM agendamentos
                 WHERE id_medico = ? AND DATE(data_hora) = DATE('now','localtime')
                 AND status IN ('pendente','confirmado')"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $total_hoje = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // Próximas consultas (7 dias)
            $stmt = $conexao_db->prepare(
                "SELECT COUNT(*) as total FROM agendamentos
                 WHERE id_medico = ? AND data_hora >= datetime('now','localtime')
                 AND data_hora <= datetime('now','localtime','+7 days')
                 AND status IN ('pendente','confirmado')"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $proximas_7d = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // Total concluídas
            $stmt = $conexao_db->prepare(
                "SELECT COUNT(*) as total FROM agendamentos WHERE id_medico = ? AND status = 'concluído'"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $total_concluidas = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // Próximas consultas do dia
            $stmt = $conexao_db->prepare(
                "SELECT a.*, c.nome AS nome_cliente, c.telefone AS telefone_cliente, c.email AS email_cliente
                 FROM agendamentos a
                 JOIN clientes c ON c.id = a.id_cliente
                 WHERE a.id_medico = ? AND DATE(a.data_hora) = DATE('now','localtime')
                 AND a.status IN ('pendente','confirmado')
                 ORDER BY a.data_hora ASC"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $consultas_hoje = $stmt->get_result()->fetch_all() ?? [];
            $stmt->close();
            ?>

            <h2>Dashboard</h2>

            <div class="painel-cards">
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="painel-card-valor"><?php echo $total_hoje; ?></div>
                    <div class="painel-card-label">Consultas hoje</div>
                </div>
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-clock"></i></div>
                    <div class="painel-card-valor"><?php echo $proximas_7d; ?></div>
                    <div class="painel-card-label">Próximos 7 dias</div>
                </div>
                <div class="painel-card">
                    <div class="painel-card-icone"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="painel-card-valor"><?php echo $total_concluidas; ?></div>
                    <div class="painel-card-label">Consultas concluídas</div>
                </div>
            </div>

            <h3 style="margin-top:2rem;">Agenda de hoje</h3>

            <?php if (empty($consultas_hoje)): ?>
                <div class="alert alert-info">Nenhuma consulta agendada para hoje.</div>
            <?php else: ?>
                <div class="lista-consultas">
                    <?php foreach ($consultas_hoje as $c): ?>
                        <div class="consulta-card">
                            <div class="consulta-hora"><?php echo date('H:i', strtotime($c['data_hora'])); ?></div>
                            <div class="consulta-info">
                                <strong><?php echo htmlspecialchars($c['nome_cliente']); ?></strong>
                                <span><?php echo get_tipo_agendamento($c['tipo']); ?> &middot; <?php echo htmlspecialchars($c['telefone_cliente'] ?? $c['email_cliente'] ?? ''); ?></span>
                            </div>
                            <span class="badge consulta-status <?php echo get_classe_status($c['status']); ?>"><?php echo get_status_agendamento($c['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($acao === 'agenda'): ?>
            <!-- ===== AGENDA (próximas consultas) ===== -->
            <?php
            $stmt = $conexao_db->prepare(
                "SELECT a.*, c.nome AS nome_cliente, c.telefone AS telefone_cliente, c.email AS email_cliente
                 FROM agendamentos a
                 JOIN clientes c ON c.id = a.id_cliente
                 WHERE a.id_medico = ? AND a.data_hora >= datetime('now','localtime')
                 AND a.status IN ('pendente','confirmado')
                 ORDER BY a.data_hora ASC
                 LIMIT 50"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $proximas = $stmt->get_result()->fetch_all() ?? [];
            $stmt->close();
            ?>

            <h2>Minha Agenda</h2>
            <p>Próximas consultas e exames confirmados.</p>

            <?php if (empty($proximas)): ?>
                <div class="alert alert-info">Nenhuma consulta futura agendada.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tabela-admin">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Paciente</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Contato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas as $c): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($c['data_hora'])); ?></td>
                                    <td><strong><?php echo date('H:i', strtotime($c['data_hora'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c['nome_cliente']); ?></td>
                                    <td><?php echo get_tipo_agendamento($c['tipo']); ?></td>
                                    <td><span class="badge <?php echo get_classe_status($c['status']); ?>"><?php echo get_status_agendamento($c['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($c['telefone_cliente'] ?? $c['email_cliente']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php elseif ($acao === 'historico'): ?>
            <!-- ===== HISTÓRICO ===== -->
            <?php
            $stmt = $conexao_db->prepare(
                "SELECT a.*, c.nome AS nome_cliente
                 FROM agendamentos a
                 JOIN clientes c ON c.id = a.id_cliente
                 WHERE a.id_medico = ?
                 ORDER BY a.data_hora DESC
                 LIMIT 100"
            );
            $stmt->bind_param('i', $id_medico);
            $stmt->execute();
            $historico = $stmt->get_result()->fetch_all() ?? [];
            $stmt->close();
            ?>

            <h2>Histórico de Consultas</h2>

            <?php if (empty($historico)): ?>
                <div class="alert alert-info">Nenhum atendimento registrado.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tabela-admin">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Paciente</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $c): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($c['data_hora'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($c['data_hora'])); ?></td>
                                    <td><?php echo htmlspecialchars($c['nome_cliente']); ?></td>
                                    <td><?php echo get_tipo_agendamento($c['tipo']); ?></td>
                                    <td><span class="badge <?php echo get_classe_status($c['status']); ?>"><?php echo get_status_agendamento($c['status']); ?></span></td>
                                    <td><?php echo formatar_valor($c['valor_total'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php elseif ($acao === 'perfil'): ?>
            <!-- ===== PERFIL ===== -->
            <?php
            $erros_perfil = $_SESSION['erros_perfil_medico'] ?? [];
            unset($_SESSION['erros_perfil_medico']);
            $flash_perfil = get_flash_message('perfil_medico');
            ?>

            <h2>Meu Perfil</h2>

            <?php if ($flash_perfil): ?>
                <div class="flash-container" role="status" aria-live="polite">
                    <div class="flash-toast flash-sucesso">
                        <span class="flash-toast-icone"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="flash-toast-texto"><?php echo htmlspecialchars($flash_perfil['mensagem']); ?></span>
                        <button type="button" class="flash-toast-fechar" aria-label="Fechar">&times;</button>
                        <span class="flash-toast-progresso"></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros_perfil)): ?>
                <div class="alert alert-error">
                    <?php foreach ($erros_perfil as $e): ?>
                        <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($e); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Alterar senha -->
            <div class="form-grupo" style="max-width:500px;">
                <div class="form-grupo-titulo"><i class="fa-solid fa-lock"></i> Alterar senha</div>
                <form method="POST" action="<?php echo $base_url; ?>backend/controllers/medico_perfil_controller.php">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
                    <input type="hidden" name="acao" value="alterar_senha_medico">

                    <label>Senha atual *</label>
                    <input type="password" name="senha_atual" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:10px;">

                    <label>Nova senha *</label>
                    <input type="password" name="senha_nova" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:10px;">

                    <label>Confirmar nova senha *</label>
                    <input type="password" name="confirmacao_senha" required style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;margin-bottom:15px;">

                    <button type="submit" class="btn-action">Salvar nova senha</button>
                </form>
            </div>

            <!-- Dados do perfil (read-only) -->
            <div class="form-grupo" style="max-width:500px;margin-top:1.5rem;">
                <div class="form-grupo-titulo"><i class="fa-solid fa-stethoscope"></i> Dados cadastrais</div>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($medico['nome']); ?></p>
                <p><strong>CRM:</strong> <?php echo htmlspecialchars($medico['crm']); ?></p>
                <p><strong>Especialidade:</strong> <?php echo htmlspecialchars($medico['nome_especialidade'] ?? '—'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($medico['login_email'] ?? $medico['email'] ?? '—'); ?></p>
                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($medico['login_telefone'] ?? $medico['telefone'] ?? '—'); ?></p>
                <small>Para alterar dados cadastrais, solicite ao administrador.</small>
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
    var key     = 'sidebar_medico_collapsed';

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
