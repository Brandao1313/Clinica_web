-- ====================================================
-- ADICIONAR NOVAS ESPECIALIDADES
-- Insere 10 novas especialidades médicas, evitando
-- duplicatas caso o script seja executado mais de uma vez.
-- ====================================================

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Clínica Geral', 'Atendimento médico geral, prevenção, diagnóstico e encaminhamento para especialistas.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Clínica Geral');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Pediatria', 'Cuidados médicos para bebês, crianças e adolescentes, do nascimento até os 18 anos.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Pediatria');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Ginecologia e Obstetrícia', 'Saúde da mulher, acompanhamento ginecológico, pré-natal e parto.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Ginecologia e Obstetrícia');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Ortopedia e Traumatologia', 'Diagnóstico e tratamento de lesões e doenças de ossos, articulações e músculos.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Ortopedia e Traumatologia');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Psiquiatria', 'Avaliação, diagnóstico e tratamento de transtornos mentais e emocionais.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Psiquiatria');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Neurologia', 'Diagnóstico e tratamento de doenças do cérebro, medula espinhal e nervos.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Neurologia');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Nutrição', 'Avaliação nutricional, orientação alimentar e planos de dieta personalizados.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Nutrição');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Fisioterapia', 'Reabilitação física, tratamento de lesões e melhora da mobilidade e qualidade de vida.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Fisioterapia');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Urologia', 'Diagnóstico e tratamento de doenças do trato urinário e do sistema reprodutor masculino.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Urologia');

INSERT INTO especialidades (nome, descricao, ativo)
SELECT 'Otorrinolaringologia', 'Diagnóstico e tratamento de doenças do ouvido, nariz e garganta.', 1
WHERE NOT EXISTS (SELECT 1 FROM especialidades WHERE nome = 'Otorrinolaringologia');

-- Fim do script
