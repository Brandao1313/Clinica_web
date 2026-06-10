<?php
// ====================================================
// ARQUIVO: backend/views/painel_cliente.php
// Descrição: Painel principal do cliente logado
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar autenticação
require_login();

// Variáveis
$conexao_db = Conexao::getInstance()->getConexao();
$id_cliente = $_SESSION['id_cliente'];
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');

// Obter dados do cliente
$stmt = $conexao_db->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->bind_param('i', $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Clínica</title>
    <link rel="stylesheet" href="../../estilo.css">
    <style>
        .painel-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .painel-sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .painel-sidebar h3 {
            color: #007b83;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .menu-item {
            display: block;
            padding: 12px;
            margin-bottom: 8px;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
            background: #f5f5f5;
            transition: all 0.3s;
        }

        .menu-item:hover, .menu-item.active {
            background: #007b83;
            color: white;
        }

        .painel-conteudo {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .painel-conteudo h2 {
            color: #007b83;
            margin-bottom: 20px;
            border-bottom: 2px solid #007b83;
            padding-bottom: 10px;
        }

        .info-card {
            background: #f9f9f9;
            border-left: 4px solid #007b83;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .info-card strong {
            color: #007b83;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

        .btn-action {
            display: inline-block;
            padding: 10px 15px;
            background: #007b83;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-action:hover {
            background: #005a60;
        }

        .btn-action.secondary {
            background: #ccc;
            color: #333;
        }

        .btn-action.secondary:hover {
            background: #bbb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th {
            background: #007b83;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        table tr:hover {
            background: #f5f5f5;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .painel-container {
                grid-template-columns: 1fr;
            }
            .painel-sidebar {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>

<body>
    <nav>
        <ul>
            <li><a href="../../index.html"><img src="../../imagens/logo.png" alt="Logo"></a></li>
            <li><a href="../../exames.php">Exames</a></li>
            <li><a href="../../especialidades.php">Especialidades</a></li>
            <a href="../auth/deslogar.php" class="meu-btn">Sair</a>
        </ul>
    </nav>

    <div class="painel-container">
        <!-- SIDEBAR COM MENU -->
        <aside class="painel-sidebar">
            <h3>Menu</h3>
            <a href="?acao=dashboard" class="menu-item <?php echo $acao === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
            <a href="?acao=perfil" class="menu-item <?php echo $acao === 'perfil' ? 'active' : ''; ?>">👤 Meu Perfil</a>
            <a href="?acao=agendamentos" class="menu-item <?php echo $acao === 'agendamentos' ? 'active' : ''; ?>">📅 Agendamentos</a>
            <a href="?acao=agendar" class="menu-item <?php echo $acao === 'agendar' ? 'active' : ''; ?>">➕ Agendar Consulta</a>
            <a href="?acao=exames" class="menu-item <?php echo $acao === 'exames' ? 'active' : ''; ?>">🧬 Solicitar Exame</a>
            <?php if (is_admin()): ?>
                <a href="painel_admin.php" class="menu-item">⚙️ Painel Admin</a>
            <?php endif; ?>
            <a href="../auth/deslogar.php" class="menu-item">🚪 Sair</a>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="painel-conteudo">
            <?php if ($acao === 'dashboard'): ?>
                <!-- DASHBOARD -->
                <h2>🏠 Bem-vindo, <?php echo htmlspecialchars($cliente['nome']); ?>!</h2>

                <div class="info-card">
                    <strong>Última atualização de perfil:</strong><br>
                    <?php echo formatar_data_hora($cliente['data_atualizacao']); ?>
                </div>

                <h3 style="color: #007b83; margin-top: 20px;">Seus próximos agendamentos</h3>

                <?php
                    $stmt = $conexao_db->prepare(
                        'SELECT a.*, COALESCE(e.nome, sp.nome) as nome_item, a.tipo
                         FROM agendamentos a
                         LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                         LEFT JOIN exames e ON a.id_exame = e.id
                         WHERE a.id_cliente = ? AND a.status != "cancelado" AND a.data_hora > NOW()
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
                    <table style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Item</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ag = $agendamentos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                                    <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($ag['nome_item']); ?></td>
                                    <td><span class="badge <?php echo 'badge-' . get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($acao === 'perfil'): ?>
                <!-- PERFIL -->
                <h2>👤 Meu Perfil</h2>

                <div class="info-card">
                    <strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome']); ?>
                </div>

                <div class="info-card">
                    <strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?>
                </div>

                <div class="info-card">
                    <strong>CPF:</strong> <?php echo formatar_cpf($cliente['cpf']); ?>
                </div>

                <div class="info-card">
                    <strong>Telefone:</strong> <?php echo !empty($cliente['telefone']) ? formatar_telefone($cliente['telefone']) : '-'; ?>
                </div>

                <div class="info-card">
                    <strong>Data de Nascimento:</strong> <?php echo !empty($cliente['data_nascimento']) ? formatar_data($cliente['data_nascimento']) . ' (' . calcular_idade($cliente['data_nascimento']) . ' anos)' : '-'; ?>
                </div>

                <div class="info-card">
                    <strong>Membro desde:</strong> <?php echo formatar_data($cliente['data_cadastro']); ?>
                </div>

                <p style="margin-top: 20px;">
                    <a href="?acao=editar_perfil" class="btn-action">Editar Perfil</a>
                    <a href="?acao=alterar_senha" class="btn-action secondary">Alterar Senha</a>
                </p>

            <?php elseif ($acao === 'agendamentos'): ?>
                <!-- AGENDAMENTOS -->
                <h2>📅 Meus Agendamentos</h2>

                <?php
                    $stmt = $conexao_db->prepare(
                        'SELECT a.*, COALESCE(e.nome, sp.nome) as nome_item, a.tipo
                         FROM agendamentos a
                         LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                         LEFT JOIN exames e ON a.id_exame = e.id
                         WHERE a.id_cliente = ?
                         ORDER BY a.data_hora DESC'
                    );
                    $stmt->bind_param('i', $id_cliente);
                    $stmt->execute();
                    $agendamentos = $stmt->get_result();
                    $stmt->close();

                    if ($agendamentos->num_rows === 0):
                ?>
                    <p>Nenhum agendamento.</p>
                    <a href="?acao=agendar" class="btn-action">Criar agendamento</a>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Item</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ag = $agendamentos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                                    <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($ag['nome_item']); ?></td>
                                    <td><span class="badge <?php echo 'badge-' . get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
                                    <td>
                                        <?php if (pode_cancelar_agendamento($ag['status'])): ?>
                                            <a href="?acao=cancelar_agendamento&id=<?php echo $ag['id']; ?>" class="btn-action secondary" onclick="return confirm('Deseja cancelar?')">Cancelar</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($acao === 'agendar'): ?>
                <!-- AGENDAR CONSULTA -->
                <h2>➕ Agendar Consulta</h2>

                <form method="POST" action="../controllers/agendamento_controller.php" style="max-width: 500px;">
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
                        <label for="data_hora"><strong>Data e Hora *</strong></label>
                        <input type="datetime-local" id="data_hora" name="data_hora" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="notas"><strong>Observações</strong></label>
                        <textarea id="notas" name="notas" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"></textarea>
                    </div>

                    <input type="hidden" name="acao" value="agendar_consulta">
                    <button type="submit" class="btn-action">Agendar</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Voltar</a>
                </form>

            <?php elseif ($acao === 'exames'): ?>
                <!-- SOLICITAR EXAME -->
                <h2>🧬 Solicitar Exame</h2>

                <form method="POST" action="../controllers/agendamento_controller.php" style="max-width: 500px;">
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

                    <div style="margin-bottom: 15px;">
                        <label for="notas_exame"><strong>Observações</strong></label>
                        <textarea id="notas_exame" name="notas" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"></textarea>
                    </div>

                    <input type="hidden" name="acao" value="agendar_exame">
                    <button type="submit" class="btn-action">Solicitar Exame</button>
                    <a href="?acao=agendamentos" class="btn-action secondary">Voltar</a>
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
                        ❌ Agendamento não encontrado ou não pode ser cancelado.
                    </div>
                <?php else: ?>
                    <h2>Cancelar Agendamento</h2>
                    <div class="info-card">
                        <strong>Tem certeza que deseja cancelar este agendamento?</strong>
                    </div>

                    <form method="POST" action="../controllers/agendamento_controller.php">
                        <input type="hidden" name="acao" value="cancelar_agendamento">
                        <input type="hidden" name="id_agendamento" value="<?php echo $id_agendamento; ?>">
                        <button type="submit" class="btn-action" style="background: #dc3545;">Sim, cancelar</button>
                        <a href="?acao=agendamentos" class="btn-action secondary">Não, voltar</a>
                    </form>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-error">
                    ❌ Ação não encontrada.
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>
