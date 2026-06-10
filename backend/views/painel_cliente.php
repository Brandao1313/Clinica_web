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

$base_url = '../../';
$titulo_pagina = 'Meu Painel - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../../includes/header.php';
?>

    <div class="painel-container">
        <?php
            $flash_agendamento = get_flash_message('agendamento');
            $flash_login = get_flash_message('login');
        ?>
        <?php if ($flash_login): ?>
            <div class="alert alert-success" style="grid-column: 1 / -1;">✅ <?php echo htmlspecialchars($flash_login['mensagem']); ?></div>
        <?php endif; ?>
        <?php if ($flash_agendamento): ?>
            <div class="alert <?php echo $flash_agendamento['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error'; ?>" style="grid-column: 1 / -1;">
                <?php echo $flash_agendamento['tipo'] === 'sucesso' ? '✅' : '❌'; ?> <?php echo htmlspecialchars($flash_agendamento['mensagem']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['erros_agendamento'])): ?>
            <div class="alert alert-error" style="grid-column: 1 / -1;">
                <?php foreach ($_SESSION['erros_agendamento'] as $erro): ?>
                    <p>❌ <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['erros_agendamento']); ?>
            </div>
        <?php endif; ?>
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
                                    <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
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
                                    <td><span class="badge <?php echo get_classe_status($ag['status']); ?>"><?php echo get_status_agendamento($ag['status']); ?></span></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
