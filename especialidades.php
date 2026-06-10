<?php
// ====================================================
// ARQUIVO: especialidades.php
// Descrição: Página dinâmica de especialidades
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';
require_once __DIR__ . '/backend/utils/funcoes_gerais.php';

$conexao_db = Conexao::getInstance()->getConexao();

$base_url = '';
$titulo_pagina = 'Especialidades - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/includes/header.php';
?>

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
                        <div class="card-icone"><?php echo obter_icone_especialidade($esp['nome']); ?></div>
                        <h3><?php echo htmlspecialchars($esp['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($esp['descricao']); ?></p>

                        <div class="card-meta">
                            <span class="card-tempo">⏱️ <?php echo obter_tempo_estimado($esp['id']); ?></span>
                            <?php if (eh_popular($esp['id'])): ?>
                                <span class="card-popular">⭐ Mais agendado</span>
                            <?php endif; ?>
                        </div>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
