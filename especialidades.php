<?php
// ====================================================
// ARQUIVO: especialidades.php
// Descrição: Página dinâmica de especialidades
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';

$conexao_db = Conexao::getInstance()->getConexao();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Especialidades - Clínica Saúde e Bem-Estar</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .especialidades-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .especialidades-container h2 {
            text-align: center;
            color: #007b83;
            font-size: 2.5rem;
            margin-bottom: 50px;
        }

        .especialidades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .especialidade-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .especialidade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .especialidade-card h3 {
            color: #007b83;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .especialidade-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .btn-agendar {
            display: inline-block;
            background: #007b83;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-agendar:hover {
            background: #005a60;
        }

        .btn-login {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .btn-login:hover {
            background: #c82333;
        }

        .nenhum {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .especialidades-container h2 {
                font-size: 1.8rem;
            }

            .especialidades-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.html"><img src="imagens/logo.png" alt="Logo"></a></li>
            <li><a href="exames.php">Exames</a></li>
            <li><a href="especialidades.php">Especialidades</a></li>
            <?php if (is_autenticado()): ?>
                <a href="backend/auth/deslogar.php" class="meu-btn">Sair (<?php echo htmlspecialchars($_SESSION['nome_cliente']); ?>)</a>
            <?php else: ?>
                <a href="cadastro/login.php" class="meu-btn">Login</a>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="especialidades-container">
        <h2>Nossas Especialidades</h2>

        <?php
            $stmt = $conexao_db->prepare('SELECT * FROM especialidades WHERE ativo = 1 ORDER BY nome');
            $stmt->execute();
            $especialidades = $stmt->get_result();
            $stmt->close();

            if ($especialidades->num_rows === 0):
        ?>
            <div class="nenhum">
                <p>Nenhuma especialidade disponível no momento.</p>
            </div>
        <?php else: ?>
            <div class="especialidades-grid">
                <?php while ($esp = $especialidades->fetch_assoc()): ?>
                    <div class="especialidade-card">
                        <h3><?php echo htmlspecialchars($esp['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($esp['descricao']); ?></p>

                        <?php if (is_autenticado()): ?>
                            <a href="backend/views/painel_cliente.php?acao=agendar" class="btn-agendar">Agendar Consulta</a>
                        <?php else: ?>
                            <a href="cadastro/login.php" class="btn-login">Faça login para agendar</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
