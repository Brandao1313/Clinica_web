<?php
// ====================================================
// ARQUIVO: backend/views/painel_admin.php
// Descrição: Painel administrativo simples
// ====================================================

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/seguranca.php';
require_once __DIR__ . '/../utils/funcoes_gerais.php';

// Verificar permissões
require_admin();

$conexao_db = Conexao::getInstance()->getConexao();
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');
$pagina = intval($_GET['pagina'] ?? 1);
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Clínica</title>
    <link rel="stylesheet" href="../../estilo.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .admin-container h2 {
            color: #007b83;
            margin-bottom: 20px;
            border-bottom: 2px solid #007b83;
            padding-bottom: 10px;
        }

        .admin-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .admin-btn {
            padding: 10px 20px;
            background: #007b83;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .admin-btn.active {
            background: #005a60;
        }

        .admin-btn:hover {
            background: #005a60;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .admin-table th {
            background: #007b83;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }

        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .admin-table tr:hover {
            background: #f9f9f9;
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

        .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .action-edit {
            background: #007b83;
            color: white;
        }

        .action-delete {
            background: #dc3545;
            color: white;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            margin: 0 5px;
            background: #f0f0f0;
            border-radius: 3px;
            text-decoration: none;
            display: inline-block;
        }

        .pagination a:hover {
            background: #007b83;
            color: white;
        }

        .pagination .active {
            background: #007b83;
            color: white;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #999;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.5rem;
            color: #007b83;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .admin-table {
                font-size: 12px;
            }

            .admin-table th, .admin-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 3px 6px;
                font-size: 10px;
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
            <a href="../auth/deslogar.php" class="meu-btn">Sair (Admin)</a>
        </ul>
    </nav>

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
                                <span class="badge <?php echo 'badge-' . get_classe_status($ag['status']); ?>">
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

</body>

</html>
