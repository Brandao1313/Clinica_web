<?php
// ====================================================
// ARQUIVO: index.php
// Descrição: Página inicial pública
// ====================================================

require_once __DIR__ . '/backend/config/conexao.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/utils/seguranca.php';
require_once __DIR__ . '/backend/utils/funcoes_gerais.php';

$conexao_db = Conexao::getInstance()->getConexao();

// Especialidades destaque (até 6)
$stmt = $conexao_db->prepare('SELECT * FROM especialidades WHERE ativo = 1 ORDER BY nome LIMIT 6');
$stmt->execute();
$resultado_esp = $stmt->get_result();
$especialidades = [];
while ($row = $resultado_esp->fetch_assoc()) {
    $especialidades[] = $row;
}
$stmt->close();

// Médicos destaque (até 4)
$stmt = $conexao_db->prepare(
    'SELECT m.*, e.nome AS especialidade_nome
     FROM medicos m
     JOIN especialidades e ON m.id_especialidade = e.id
     WHERE m.ativo = 1
     ORDER BY m.id
     LIMIT 4'
);
$stmt->execute();
$resultado_med = $stmt->get_result();
$medicos = [];
while ($row = $resultado_med->fetch_assoc()) {
    $medicos[] = $row;
}
$stmt->close();

// Stats rápidos para barra abaixo do hero
$stmt = $conexao_db->prepare('SELECT COUNT(*) AS total FROM especialidades WHERE ativo = 1');
$stmt->execute();
$total_especialidades = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexao_db->prepare('SELECT COUNT(*) AS total FROM medicos WHERE ativo = 1');
$stmt->execute();
$total_medicos = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$base_url = '';
$titulo_pagina = 'Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/backend/includes/header.php';
?>

    <!-- ================================================
         HERO
         ================================================ -->
    <section class="hero">
        <img src="assets/imagens/inicial.png" alt="Clínica Saúde &amp; Bem-Estar" class="hero-imagem">
        <div class="hero-conteudo">
            <span class="hero-tag">
                <i class="fa-solid fa-shield-heart"></i> Saúde &amp; Bem-Estar
            </span>
            <h1>Cuidamos da Sua<br>Saúde com Excelência</h1>
            <p>Medicina humanizada, tecnologia de ponta e especialistas dedicados ao seu bem-estar. Agende sua consulta online em poucos cliques.</p>
            <div class="hero-acoes">
                <?php if (!is_autenticado()): ?>
                    <a href="cadastro/login.php" class="btn btn-hero">
                        <i class="fa-solid fa-calendar-check"></i> Agendar Consulta
                    </a>
                    <a href="especialidades.php" class="btn btn-hero-outline">
                        <i class="fa-solid fa-stethoscope"></i> Nossas Especialidades
                    </a>
                <?php elseif (is_admin()): ?>
                    <a href="backend/views/painel_admin.php" class="btn btn-hero">
                        <i class="fa-solid fa-gear"></i> Acessar Painel Admin
                    </a>
                    <a href="especialidades.php" class="btn btn-hero-outline">
                        <i class="fa-solid fa-stethoscope"></i> Especialidades
                    </a>
                <?php elseif (is_medico()): ?>
                    <a href="backend/views/painel_medico.php" class="btn btn-hero">
                        <i class="fa-solid fa-user-doctor"></i> Meu Painel
                    </a>
                <?php elseif (is_recepcionista()): ?>
                    <a href="backend/views/painel_recepcionista.php" class="btn btn-hero">
                        <i class="fa-solid fa-clipboard-list"></i> Meu Painel
                    </a>
                <?php else: ?>
                    <a href="backend/views/painel_cliente.php?acao=agendar" class="btn btn-hero">
                        <i class="fa-solid fa-calendar-check"></i> Agendar Consulta
                    </a>
                    <a href="backend/views/painel_cliente.php?acao=agendamentos" class="btn btn-hero-outline">
                        <i class="fa-solid fa-clock"></i> Meus Agendamentos
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats bar -->
    <div class="hero-stats">
        <div class="hero-stats-inner">
            <div class="hero-stat-item">
                <strong><?= $total_medicos ?>+</strong>
                <span>Médicos Especializados</span>
            </div>
            <div class="hero-stat-item">
                <strong><?= $total_especialidades ?>+</strong>
                <span>Especialidades</span>
            </div>
            <div class="hero-stat-item">
                <strong>10+</strong>
                <span>Anos de Experiência</span>
            </div>
            <div class="hero-stat-item">
                <strong>500+</strong>
                <span>Pacientes Atendidos</span>
            </div>
        </div>
    </div>

    <!-- ================================================
         ESPECIALIDADES DESTAQUE
         ================================================ -->
    <?php if (!empty($especialidades)): ?>
    <section class="home-secao">
        <div class="secao-cabecalho">
            <h2>Nossas Especialidades</h2>
            <span class="secao-detalhe"></span>
            <p>Contamos com especialistas em diversas áreas para cuidar de você e sua família com qualidade e dedicação.</p>
        </div>

        <div class="especialidades-grid">
            <?php foreach ($especialidades as $esp): ?>
                <div class="especialidade-card">
                    <div class="card-icone"><?= obter_icone_especialidade($esp['nome']) ?></div>
                    <h3><?= htmlspecialchars($esp['nome']) ?></h3>
                    <p><?= htmlspecialchars(truncar_texto($esp['descricao'] ?? '', 100)) ?></p>
                    <div class="card-meta">
                        <span class="card-tempo">
                            <i class="fa-regular fa-clock"></i>
                            <?= obter_tempo_estimado($esp['id']) ?>
                        </span>
                        <?php if (eh_popular($esp['id'])): ?>
                            <span class="card-popular">
                                <i class="fa-solid fa-star"></i> Mais agendado
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (is_autenticado() && !is_admin() && !is_medico() && !is_recepcionista()): ?>
                        <a href="backend/views/painel_cliente.php?acao=agendar" class="btn-agendar">Agendar Consulta</a>
                    <?php else: ?>
                        <a href="cadastro/login.php" class="btn-agendar">Agendar Consulta</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="home-secao-rodape">
            <a href="especialidades.php" class="btn btn-secondary">
                Ver todas as especialidades <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================
         SOBRE A CLÍNICA
         ================================================ -->
    <section class="sobre-clinica">
        <div class="conteudo-texto">
            <h2>Cuidado que Transforma, Saúde que Inspira!</h2>
            <p>
                Na Clínica Saúde &amp; Bem-Estar, acreditamos que o cuidado vai além do consultório.
                Unimos tecnologia de ponta a um atendimento profundamente humanizado para garantir
                que você e sua família recebam o melhor suporte em cada etapa da jornada de saúde.
            </p>
            <p>
                Nossa missão é promover qualidade de vida através de diagnósticos precisos e
                especialidades focadas no seu equilíbrio físico e mental.
            </p>
            <a href="sobre.php" class="btn-sobre">
                Sobre Nós <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="galeria-fotos">
            <img src="assets/imagens/clinica1.png" alt="Consultório moderno">
            <img src="assets/imagens/clinica2.png" alt="Equipamento médico">
        </div>
    </section>

    <!-- ================================================
         MÉDICOS DESTAQUE
         ================================================ -->
    <?php if (!empty($medicos)): ?>
    <section class="home-secao">
        <div class="secao-cabecalho">
            <h2>Nossos Médicos</h2>
            <span class="secao-detalhe"></span>
            <p>Profissionais altamente qualificados e dedicados ao seu cuidado, com experiência e atenção individualizada.</p>
        </div>

        <div class="medicos-grid">
            <?php foreach ($medicos as $med): ?>
                <div class="medico-card">
                    <div class="medico-card-cabecalho">
                        <?php if (!empty($med['foto'])): ?>
                            <img
                                src="<?= htmlspecialchars($med['foto']) ?>"
                                alt="<?= htmlspecialchars($med['nome']) ?>"
                                class="medico-foto"
                            >
                        <?php else: ?>
                            <div class="medico-foto-placeholder">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3><?= htmlspecialchars($med['nome']) ?></h3>
                            <div class="medico-especialidade"><?= htmlspecialchars($med['especialidade_nome']) ?></div>
                            <div class="medico-crm">CRM <?= htmlspecialchars($med['crm']) ?></div>
                        </div>
                    </div>

                    <?php if (!empty($med['bio'])): ?>
                        <p class="medico-bio"><?= htmlspecialchars(truncar_texto($med['bio'], 120)) ?></p>
                    <?php endif; ?>

                    <div class="medico-card-acoes">
                        <a href="medico_detalhe.php?id=<?= (int) $med['id'] ?>" class="btn btn-secondary">
                            Ver Perfil
                        </a>
                        <?php if (is_autenticado() && !is_admin() && !is_medico() && !is_recepcionista()): ?>
                            <a href="backend/views/painel_cliente.php?acao=agendar" class="btn-agendar">Agendar</a>
                        <?php else: ?>
                            <a href="cadastro/login.php" class="btn-agendar">Agendar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="home-secao-rodape">
            <a href="medicos.php" class="btn btn-secondary">
                Ver todos os médicos <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================
         CONTATO
         ================================================ -->
    <section class="contato-section">
        <div class="contato-inner">
            <div class="secao-cabecalho">
                <h2>Como Nos Encontrar</h2>
                <span class="secao-detalhe"></span>
                <p>Estamos aqui para cuidar de você. Entre em contato ou agende sua visita presencial.</p>
            </div>

            <div class="contato-grid">
                <div class="contato-item">
                    <div class="contato-icone">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="contato-texto">
                        <h4>Endereço</h4>
                        <p>Rua das Flores, 123 — Centro<br>São Paulo — SP, 01000-000</p>
                    </div>
                </div>

                <div class="contato-item">
                    <div class="contato-icone">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="contato-texto">
                        <h4>Telefone</h4>
                        <a href="tel:+551130000000">(11) 3000-0000</a>
                    </div>
                </div>

                <div class="contato-item">
                    <div class="contato-icone contato-icone-whatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div class="contato-texto">
                        <h4>WhatsApp</h4>
                        <a href="https://wa.me/5511990000000" target="_blank" rel="noopener">
                            (11) 99000-0000
                        </a>
                    </div>
                </div>

                <div class="contato-item">
                    <div class="contato-icone">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="contato-texto">
                        <h4>Horário de Funcionamento</h4>
                        <p>Segunda a Sexta: 8h às 18h<br>Sábado: 8h às 13h</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================
         CTA FINAL
         ================================================ -->
    <section class="cta-section">
        <h2>Pronto para Cuidar da Sua Saúde?</h2>
        <p>Agende sua consulta online agora mesmo. Rápido, fácil e sem complicação.</p>
        <div class="cta-acoes">
            <?php if (!is_autenticado()): ?>
                <a href="cadastro/login.php" class="btn btn-hero">
                    <i class="fa-solid fa-calendar-check"></i> Agendar Consulta
                </a>
                <a href="cadastro/criar_conta.php" class="btn btn-hero-outline">
                    <i class="fa-solid fa-user-plus"></i> Criar Conta Grátis
                </a>
            <?php elseif (is_admin()): ?>
                <a href="backend/views/painel_admin.php" class="btn btn-hero">
                    <i class="fa-solid fa-gauge"></i> Acessar Painel Admin
                </a>
            <?php elseif (is_medico()): ?>
                <a href="backend/views/painel_medico.php" class="btn btn-hero">
                    <i class="fa-solid fa-user-doctor"></i> Meu Painel
                </a>
            <?php elseif (is_recepcionista()): ?>
                <a href="backend/views/painel_recepcionista.php" class="btn btn-hero">
                    <i class="fa-solid fa-clipboard-list"></i> Meu Painel
                </a>
            <?php else: ?>
                <a href="backend/views/painel_cliente.php?acao=agendar" class="btn btn-hero">
                    <i class="fa-solid fa-calendar-check"></i> Agendar Consulta
                </a>
                <a href="cadastro/criar_conta.php" class="btn btn-hero-outline">
                    <i class="fa-solid fa-user-plus"></i> Criar Conta Grátis
                </a>
            <?php endif; ?>
        </div>
    </section>

<?php require_once __DIR__ . '/backend/includes/footer.php'; ?>
