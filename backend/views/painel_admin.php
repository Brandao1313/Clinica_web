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

$conexao_db = Conexao::getInstance()->getConexao();
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');
$pagina = intval($_GET['pagina'] ?? 1);
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

$base_url = '../../';
$titulo_pagina = 'Painel Admin - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/../../includes/header.php';
?>

    <div class="admin-container">
        <h2>⚙️ Painel Administrativo</h2>

        <div class="admin-menu">
            <a href="?acao=dashboard" class="admin-btn <?php echo $acao === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="?acao=clientes" class="admin-btn <?php echo $acao === 'clientes' ? 'active' : ''; ?>">Clientes</a>
            <a href="?acao=agendamentos" class="admin-btn <?php echo $acao === 'agendamentos' ? 'active' : ''; ?>">Agendamentos</a>
            <a href="?acao=especialidades" class="admin-btn <?php echo $acao === 'especialidades' ? 'active' : ''; ?>">Especialidades</a>
            <a href="?acao=exames" class="admin-btn <?php echo $acao === 'exames' ? 'active' : ''; ?>">Exames</a>
            <a href="painel_cliente.php" class="admin-btn">← Voltar</a>
        </div>

        <?php if ($acao === 'dashboard'): ?>
            <!-- DASHBOARD -->
            <div class="stats">
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

                    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM especialidades');
                    $stmt->execute();
                    $total_especialidades = $stmt->get_result()->fetch_assoc()['total'];
                    $stmt->close();
                ?>
                <div class="stat-card">
                    <h3>Clientes Cadastrados</h3>
                    <div class="stat-number"><?php echo $total_clientes; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Agendamentos Pendentes</h3>
                    <div class="stat-number"><?php echo $agendamentos_pendentes; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Agendamentos Confirmados</h3>
                    <div class="stat-number"><?php echo $agendamentos_confirmados; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Especialidades</h3>
                    <div class="stat-number"><?php echo $total_especialidades; ?></div>
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
                    <a href="?acao=clientes&pagina=<?php echo $pagina - 1; ?>">← Anterior</a>
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

            <?php
                $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM agendamentos');
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $total_paginas = ceil($total / $itens_por_pagina);

                $stmt = $conexao_db->prepare(
                    'SELECT a.*, c.nome as cliente_nome, COALESCE(e.nome, sp.nome) as item_nome, a.tipo
                     FROM agendamentos a
                     LEFT JOIN clientes c ON a.id_cliente = c.id
                     LEFT JOIN especialidades sp ON a.id_especialidade = sp.id
                     LEFT JOIN exames e ON a.id_exame = e.id
                     ORDER BY a.data_hora DESC
                     LIMIT ? OFFSET ?'
                );
                $stmt->bind_param('ii', $itens_por_pagina, $offset);
                $stmt->execute();
                $agendamentos = $stmt->get_result();
                $stmt->close();
            ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Item</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($ag = $agendamentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ag['cliente_nome']); ?></td>
                            <td><?php echo get_tipo_agendamento($ag['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($ag['item_nome']); ?></td>
                            <td><?php echo formatar_data_hora($ag['data_hora']); ?></td>
                            <td>
                                <span class="badge <?php echo get_classe_status($ag['status']); ?>">
                                    <?php echo get_status_agendamento($ag['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?acao=agendamentos&pagina=<?php echo $pagina - 1; ?>">← Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <span class="<?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a href="?acao=agendamentos&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </span>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?acao=agendamentos&pagina=<?php echo $pagina + 1; ?>">Próxima →</a>
                <?php endif; ?>
            </div>

        <?php elseif ($acao === 'especialidades'): ?>
            <!-- LISTA DE ESPECIALIDADES -->
            <h3>Especialidades</h3>

            <?php
                $stmt = $conexao_db->prepare('SELECT * FROM especialidades ORDER BY nome');
                $stmt->execute();
                $especialidades = $stmt->get_result();
                $stmt->close();
            ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($esp = $especialidades->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($esp['nome']); ?></td>
                            <td><?php echo truncar_texto(htmlspecialchars($esp['descricao']), 50); ?></td>
                            <td>
                                <span class="badge <?php echo $esp['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $esp['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php elseif ($acao === 'exames'): ?>
            <!-- LISTA DE EXAMES -->
            <h3>Exames</h3>

            <?php
                $stmt = $conexao_db->prepare('SELECT * FROM exames ORDER BY nome');
                $stmt->execute();
                $exames = $stmt->get_result();
                $stmt->close();
            ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exame = $exames->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exame['nome']); ?></td>
                            <td><?php echo truncar_texto(htmlspecialchars($exame['descricao']), 50); ?></td>
                            <td><?php echo formatar_valor($exame['preco']); ?></td>
                            <td>
                                <span class="badge <?php echo $exame['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $exame['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>Ação não encontrada.</p>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
