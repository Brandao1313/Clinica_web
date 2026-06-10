<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saúde e Bem Estar 👩‍⚕️</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php"><img src="imagens/logo.png" alt="Logo"></a></li>
            <li><a href="especialidades.php">Especialidades</a></li>
            <li><a href="exames.php">Exames</a></li>
            <li><a href="cadastro/login.html" class="meu-btn">Login</a></li>
        </ul>
    </nav>

    <div style="padding: 40px; text-align: center;">
        <h1>Bem-vindo à Clínica Saúde & Bem-Estar 👩‍⚕️</h1>
        <p>Sistema de agendamento de consultas e exames</p>

        <?php
            // Teste de conexão com BD
            require_once __DIR__ . '/backend/config/conexao.php';

            try {
                $resultado = $db->query("SELECT COUNT(*) as total FROM especialidades");
                $row = $resultado->fetch_assoc();
                echo '<p style="color: green; font-weight: bold;">✅ Conexão com banco de dados OK!</p>';
                echo '<p>Especialidades no banco: ' . $row['total'] . '</p>';
            } catch (Exception $e) {
                echo '<p style="color: red; font-weight: bold;">❌ Erro ao conectar: ' . $e->getMessage() . '</p>';
            }
        ?>
    </div>
</body>
</html>
