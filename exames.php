<?php
// ====================================================
// ARQUIVO: exames.php
// Descrição: Página dinâmica de exames
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';
require_once __DIR__ . '/backend/utils/funcoes_gerais.php';

$conexao_db = Conexao::getInstance()->getConexao();

$base_url = '';
$titulo_pagina = 'Exames - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/backend/includes/header.php';
?>

    <div class="exames-container">
        <h2>Nossos Exames</h2>

        <?php
            $stmt = $conexao_db->prepare('SELECT * FROM exames WHERE ativo = 1 ORDER BY nome');
            $stmt->execute();
            $exames = $stmt->get_result();
            $stmt->close();

            if ($exames->num_rows === 0):
        ?>
            <div class="nenhum">
                <p>Nenhum exame disponível no momento.</p>
            </div>
        <?php else: ?>
            <div class="exames-grid">
                <?php while ($exame = $exames->fetch_assoc()): ?>
                    <div class="exame-card">
                        <div class="card-icone"><?php echo obter_icone_exame($exame['nome']); ?></div>
                        <h3><?php echo htmlspecialchars($exame['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($exame['descricao']); ?></p>
                        <div class="preco"><?php echo formatar_valor($exame['preco']); ?></div>

                        <div class="card-meta">
                            <span class="card-tempo">⏱️ <?php echo obter_tempo_estimado($exame['id']); ?></span>
                            <?php if (eh_popular($exame['id'])): ?>
                                <span class="card-popular">⭐ Mais agendado</span>
                            <?php endif; ?>
                        </div>

                        <?php if (is_autenticado()): ?>
                            <a href="backend/views/painel_cliente.php?acao=exames" class="btn-solicitar">Solicitar Exame</a>
                        <?php else: ?>
                            <a href="cadastro/login.php" class="btn-login">Faça login para solicitar</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/backend/includes/footer.php'; ?>
