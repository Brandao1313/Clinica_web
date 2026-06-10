<?php
// ====================================================
// ARQUIVO: index.php
// Descrição: Página inicial
// ====================================================

$base_url = '';
$titulo_pagina = 'Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/includes/header.php';
?>

    <div class="inicial">
        <img src="imagens/inicial.png" alt="Recepção da Clínica Saúde & Bem-Estar">
    </div>

    <section class="sobre-clinica">
        <div class="conteudo-texto">
            <h2>Cuidado que Transforma, Saúde que Inspira!</h2>
            <p>
                Na Clínica Saúde & Bem-Estar, acreditamos que o cuidado vai além do consultório.
                Unimos tecnologia de ponta a um atendimento profundamente humanizado para garantir
                que você e sua família recebam o melhor suporte em cada etapa da jornada de saúde.
            </p>
            <p>
                Nossa missão é promover qualidade de vida através de diagnósticos precisos e
                especialidades focadas no seu equilíbrio físico e mental.
            </p>
            <a href="sobre.php" class="btn-sobre">Sobre Nós</a>
        </div>

        <div class="galeria-fotos">
            <img src="imagens/clinica1.png" alt="Consultório">
            <img src="imagens/clinica2.png" alt="Equipamento Médico">
        </div>
    </section>

    <section class="destaques">
        <a href="especialidades.php" class="destaque-card">
            <div class="icone">🏥</div>
            <h3>Especialidades</h3>
            <p>Conheça as especialidades médicas disponíveis e agende sua consulta.</p>
        </a>
        <a href="exames.php" class="destaque-card">
            <div class="icone">🧬</div>
            <h3>Exames</h3>
            <p>Solicite exames laboratoriais e de imagem com praticidade.</p>
        </a>
        <?php if (is_autenticado()): ?>
            <a href="backend/views/painel_cliente.php" class="destaque-card">
                <div class="icone">📅</div>
                <h3>Meus Agendamentos</h3>
                <p>Acompanhe e gerencie suas consultas e exames marcados.</p>
            </a>
        <?php else: ?>
            <a href="cadastro/criar_conta.php" class="destaque-card">
                <div class="icone">👤</div>
                <h3>Crie sua conta</h3>
                <p>Cadastre-se para agendar consultas e exames online.</p>
            </a>
        <?php endif; ?>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
