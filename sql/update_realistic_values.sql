-- ====================================================
-- ATUALIZAÇÃO DE VALORES REALISTAS PARA TESTES
-- Execute após sql/populate_test_data.sql
--
-- O que este script faz:
--  1. Aumenta o valor da consulta de cada médico e ajusta o
--     percentual de repasse para 60% (clínica fica com 40%).
--  2. Aumenta o preço dos exames.
--  3. Recalcula valor_total/valor_medico dos agendamentos de
--     teste já existentes (criados por populate_test_data.sql)
--     para refletir os novos valores.
--  4. Atualiza a data de cadastro dos clientes para datas
--     fictícias no passado (para o card "Membro desde").
--  5. Insere agendamentos concluídos adicionais nos últimos
--     meses, com diferentes status de pagamento.
--  6. Insere registros de pagamentos_medicos coerentes com os
--     novos valores.
-- ====================================================

---------------------------------------------
-- 1. NOVOS VALORES DE CONSULTA POR ESPECIALIDADE
---------------------------------------------
UPDATE medicos SET valor_consulta =
    CASE id_especialidade
        WHEN (SELECT id FROM especialidades WHERE nome = 'Cardiologia')       THEN 450.00
        WHEN (SELECT id FROM especialidades WHERE nome = 'Dermatologia')      THEN 380.00
        WHEN (SELECT id FROM especialidades WHERE nome = 'Oftalmologia')      THEN 350.00
        WHEN (SELECT id FROM especialidades WHERE nome = 'Odontologia')       THEN 320.00
        WHEN (SELECT id FROM especialidades WHERE nome = 'Pneumologia')       THEN 420.00
        WHEN (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia') THEN 400.00
        ELSE valor_consulta
    END,
    percentual_medico = 60.00;

---------------------------------------------
-- 2. NOVOS PREÇOS DE EXAMES
---------------------------------------------
UPDATE exames SET preco =
    CASE nome
        WHEN 'Eletrocardiograma'           THEN 250.00
        WHEN 'Ultrassom Abdominal'         THEN 350.00
        WHEN 'Ressonância Magnética'       THEN 1200.00
        WHEN 'Tomografia Computadorizada'  THEN 850.00
        WHEN 'Hemograma Completo'          THEN 180.00
        WHEN 'Teste de Diabetes'           THEN 120.00
        ELSE preco
    END;

---------------------------------------------
-- 3. RECALCULAR AGENDAMENTOS DE TESTE EXISTENTES
--    (criados por populate_test_data.sql) com os novos valores
---------------------------------------------

-- Ana / Dr. Ricardo (Cardiologia) - concluído
UPDATE agendamentos SET valor_total = 450.00, valor_medico = 270.00
WHERE status = 'concluído'
  AND id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345')
  AND id_especialidade = (SELECT id FROM especialidades WHERE nome = 'Cardiologia');

-- Bruno / Dra. Patrícia (Dermatologia) - confirmado
UPDATE agendamentos SET valor_total = 380.00, valor_medico = 228.00
WHERE status = 'confirmado'
  AND id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456');

-- Carla / Dr. Augusto (Oftalmologia) - pendente
UPDATE agendamentos SET valor_total = 350.00, valor_medico = 210.00
WHERE status = 'pendente'
  AND tipo = 'consulta'
  AND id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567');

-- Daniel / Dra. Leticia (Odontologia) - cancelado
UPDATE agendamentos SET valor_total = 320.00, valor_medico = 192.00
WHERE status = 'cancelado'
  AND id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678');

-- Fernando / Dr. Marcos (Pneumologia) - concluído
UPDATE agendamentos SET valor_total = 420.00, valor_medico = 252.00
WHERE status = 'concluído'
  AND id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789');

-- Elisa / Exame Eletrocardiograma - pendente
UPDATE agendamentos SET valor_total = 250.00
WHERE tipo = 'exame'
  AND id_exame = (SELECT id FROM exames WHERE nome = 'Eletrocardiograma');

---------------------------------------------
-- 4. ATUALIZAR "MEMBRO DESDE" DOS CLIENTES (datas fictícias)
---------------------------------------------
UPDATE clientes SET data_cadastro = '2024-01-15 09:00:00' WHERE email = 'admin@clinica.com';
UPDATE clientes SET data_cadastro = '2024-03-10 14:30:00' WHERE email = 'joao@example.com';
UPDATE clientes SET data_cadastro = '2025-06-01 11:00:00' WHERE email = 'matue@cliente.com';
UPDATE clientes SET data_cadastro = '2025-02-10 10:15:00' WHERE email = 'ana@example.com';
UPDATE clientes SET data_cadastro = '2025-02-15 16:45:00' WHERE email = 'bruno@example.com';
UPDATE clientes SET data_cadastro = '2025-03-01 08:20:00' WHERE email = 'carla@example.com';
UPDATE clientes SET data_cadastro = '2025-04-12 13:00:00' WHERE email = 'daniel@example.com';
UPDATE clientes SET data_cadastro = '2025-05-20 17:10:00' WHERE email = 'elisa@example.com';
UPDATE clientes SET data_cadastro = '2025-06-30 09:50:00' WHERE email = 'fernando@example.com';

---------------------------------------------
-- 5. AGENDAMENTOS CONCLUÍDOS ADICIONAIS (abril/maio)
---------------------------------------------
-- Abril/2026: marcados como já repassados ao médico (pago_medico)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'joao@example.com'),     'consulta', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-04-08 09:00:00', 'concluído', 'Retorno de rotina', 450.00, 270.00, 'pago_medico', '2026-04-08'),
((SELECT id FROM clientes WHERE email = 'matue@cliente.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-04-22 09:30:00', 'concluído', 'Avaliação cardíaca', 450.00, 270.00, 'pago_medico', '2026-04-22'),
((SELECT id FROM clientes WHERE email = 'ana@example.com'),      'consulta', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), '2026-04-08 10:00:00', 'concluído', 'Consulta de rotina', 380.00, 228.00, 'pago_medico', '2026-04-08'),
((SELECT id FROM clientes WHERE email = 'bruno@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), '2026-04-22 10:30:00', 'concluído', 'Acompanhamento', 380.00, 228.00, 'pago_medico', '2026-04-22'),
((SELECT id FROM clientes WHERE email = 'carla@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), '2026-04-09 14:00:00', 'concluído', 'Exame de vista', 350.00, 210.00, 'pago_medico', '2026-04-09'),
((SELECT id FROM clientes WHERE email = 'daniel@example.com'),   'consulta', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), '2026-04-23 14:30:00', 'concluído', 'Acompanhamento', 350.00, 210.00, 'pago_medico', '2026-04-23'),
((SELECT id FROM clientes WHERE email = 'elisa@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Odontologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), '2026-04-10 08:00:00', 'concluído', 'Limpeza', 320.00, 192.00, 'pago_medico', '2026-04-10'),
((SELECT id FROM clientes WHERE email = 'fernando@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Odontologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), '2026-04-24 08:30:00', 'concluído', 'Avaliação', 320.00, 192.00, 'pago_medico', '2026-04-24'),
((SELECT id FROM clientes WHERE email = 'joao@example.com'),     'consulta', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-04-13 09:00:00', 'concluído', 'Avaliação respiratória', 420.00, 252.00, 'pago_medico', '2026-04-13'),
((SELECT id FROM clientes WHERE email = 'matue@cliente.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-04-27 09:30:00', 'concluído', 'Retorno', 420.00, 252.00, 'pago_medico', '2026-04-27'),
((SELECT id FROM clientes WHERE email = 'ana@example.com'),      'consulta', (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), '2026-04-14 14:00:00', 'concluído', 'Consulta de rotina', 400.00, 240.00, 'pago_medico', '2026-04-14'),
((SELECT id FROM clientes WHERE email = 'bruno@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), '2026-04-28 14:30:00', 'concluído', 'Endoscopia - retorno', 400.00, 240.00, 'pago_medico', '2026-04-28'),
((SELECT id FROM clientes WHERE email = 'carla@example.com'),    'exame', NULL, NULL, '2026-04-16 11:00:00', 'concluído', 'Ressonância Magnética', 1200.00, NULL, 'pago_clinica', '2026-04-16');

-- Maio/2026: já recebidos pela clínica, ainda não repassados (pago_clinica)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'daniel@example.com'),   'consulta', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-05-06 09:00:00', 'concluído', 'Consulta de rotina', 450.00, 270.00, 'pago_clinica', '2026-05-06'),
((SELECT id FROM clientes WHERE email = 'elisa@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-05-20 09:30:00', 'concluído', 'Retorno', 450.00, 270.00, 'pago_clinica', '2026-05-20'),
((SELECT id FROM clientes WHERE email = 'fernando@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), '2026-05-06 10:00:00', 'concluído', 'Avaliação de pele', 380.00, 228.00, 'pago_clinica', '2026-05-06'),
((SELECT id FROM clientes WHERE email = 'joao@example.com'),     'consulta', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), '2026-05-20 10:30:00', 'concluído', 'Retorno', 380.00, 228.00, 'pago_clinica', '2026-05-20'),
((SELECT id FROM clientes WHERE email = 'matue@cliente.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), '2026-05-07 14:00:00', 'concluído', 'Exame de vista', 350.00, 210.00, 'pago_clinica', '2026-05-07'),
((SELECT id FROM clientes WHERE email = 'ana@example.com'),      'consulta', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'),      (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), '2026-05-21 14:30:00', 'concluído', 'Retorno', 350.00, 210.00, 'pago_clinica', '2026-05-21'),
((SELECT id FROM clientes WHERE email = 'bruno@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Odontologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), '2026-05-08 08:00:00', 'concluído', 'Limpeza', 320.00, 192.00, 'pago_clinica', '2026-05-08'),
((SELECT id FROM clientes WHERE email = 'carla@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Odontologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), '2026-05-22 08:30:00', 'concluído', 'Avaliação', 320.00, 192.00, 'pago_clinica', '2026-05-22'),
((SELECT id FROM clientes WHERE email = 'daniel@example.com'),   'consulta', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-05-11 09:00:00', 'concluído', 'Avaliação respiratória', 420.00, 252.00, 'pago_clinica', '2026-05-11'),
((SELECT id FROM clientes WHERE email = 'elisa@example.com'),    'consulta', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'),       (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-05-25 09:30:00', 'concluído', 'Retorno', 420.00, 252.00, 'pago_clinica', '2026-05-25'),
((SELECT id FROM clientes WHERE email = 'fernando@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), '2026-05-12 14:00:00', 'concluído', 'Consulta de rotina', 400.00, 240.00, 'pago_clinica', '2026-05-12'),
((SELECT id FROM clientes WHERE email = 'joao@example.com'),     'consulta', (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), '2026-05-26 14:30:00', 'concluído', 'Endoscopia - retorno', 400.00, 240.00, 'pago_clinica', '2026-05-26'),
((SELECT id FROM clientes WHERE email = 'matue@cliente.com'),    'exame', NULL, NULL, '2026-05-18 11:00:00', 'concluído', 'Tomografia Computadorizada', 850.00, NULL, 'pago_clinica', '2026-05-18');

---------------------------------------------
-- 6. ATUALIZAR/INSERIR PAGAMENTOS AOS MÉDICOS
---------------------------------------------
-- Atualizar os pagamentos de maio já existentes (gerados antes
-- dos novos valores) para refletir os novos valores de repasse
UPDATE pagamentos_medicos SET valor_total_recebido = 450.00, valor_repassado = 270.00
WHERE id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345')
  AND periodo_inicio = '2026-05-01';

UPDATE pagamentos_medicos SET valor_total_recebido = 420.00, valor_repassado = 252.00
WHERE id_medico = (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789')
  AND periodo_inicio = '2026-05-01';

-- Repasses referentes a abril/2026 (2 atendimentos por médico)
INSERT INTO pagamentos_medicos (id_medico, periodo_inicio, periodo_fim, total_consultas, valor_total_recebido, valor_repassado, data_pagamento, status, observacoes) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-04-01', '2026-04-30', 2,  900.00, 540.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), '2026-04-01', '2026-04-30', 2,  760.00, 456.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), '2026-04-01', '2026-04-30', 2,  700.00, 420.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), '2026-04-01', '2026-04-30', 2,  640.00, 384.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-04-01', '2026-04-30', 2,  840.00, 504.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), '2026-04-01', '2026-04-30', 2,  800.00, 480.00, '2026-05-05', 'pago', 'Repasse referente a abril/2026');

-- Fim do script
