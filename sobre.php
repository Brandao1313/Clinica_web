<?php
// ====================================================
// ARQUIVO: sobre.php
// Descrição: Página institucional "Sobre Nós"
// ====================================================

$base_url = '';
$titulo_pagina = 'Sobre Nós - Clínica Saúde & Bem-Estar';
require_once __DIR__ . '/backend/includes/header.php';
?>

    <header class="sobre-header">
        <h1>Nossa História e Compromisso</h1>
        <p>Conheça a trajetória da Clínica Saúde & Bem-Estar</p>
    </header>

    <section class="sobre-historia">
        <div class="historia-texto">
            <h2>Como tudo começou</h2>
            <p>
                Fundada em 2018, a Clínica Saúde & Bem-Estar nasceu com o propósito claro de transformar o atendimento médico na nossa região. Percebendo a necessidade de um espaço que unisse tecnologia de ponta a um acolhimento caloroso e humano, nossos fundadores idealizaram um centro de saúde integrado.
            </p>
            <p>
                Hoje, contamos com uma equipe multidisciplinar altamente qualificada, infraestrutura moderna para exames e consultas, e o orgulho de já ter cuidado de milhares de famílias com a dedicação e o respeito que elas merecem.
            </p>
        </div>
        <div class="historia-imagem">
            <img src="assets/imagens/clinica1.png" alt="Interior da Clínica">
        </div>
    </section>

    <section class="valores-container">
        <div class="valor-card">
            <div class="icone">🎯</div>
            <h3>Missão</h3>
            <p>Promover a saúde integrada e a qualidade de vida, oferecendo diagnósticos precisos e tratamentos humanizados com excelência médica.</p>
        </div>
        <div class="valor-card">
            <div class="icone">🔭</div>
            <h3>Visão</h3>
            <p>Ser reconhecida como a principal referência em cuidado médico humanizado e inovação tecnológica em saúde na região até 2028.</p>
        </div>
        <div class="valor-card">
            <div class="icone">💚</div>
            <h3>Valores</h3>
            <p>Humanização, ética profissional, compromisso com a vida, inovação constante, transparência e respeito ao paciente.</p>
        </div>
    </section>

<?php require_once __DIR__ . '/backend/includes/footer.php'; ?>
