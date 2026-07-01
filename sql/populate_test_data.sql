-- ====================================================
-- POPULAÇÃO DE DADOS DE TESTE - CLÍNICA
-- Execute após a migração de médicos (sql/migrate_medicos.sql)
--
-- Observação: todas as referências a clientes e médicos usam
-- subconsultas por email/CRM em vez de IDs fixos, pois o
-- banco já pode conter registros (admin, clientes de teste
-- pré-existentes, etc.) e os IDs gerados pelo AUTOINCREMENT
-- não necessariamente começam em 1.
-- ====================================================

---------------------------------------------
-- 1. CLIENTES (PACIENTES) - 6 contas
---------------------------------------------
-- Senha padrão para todos: "admin123"
-- Hash bcrypt válido gerado com password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, ativo, eh_admin) VALUES
('Ana Carolina Souza', 'ana@example.com', '11122233344', '11999911111', '1990-03-15', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0),
('Bruno Mendes', 'bruno@example.com', '22233344455', '11988822222', '1985-07-22', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0),
('Carla Fernanda Dias', 'carla@example.com', '33344455566', '11977733333', '1995-12-10', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0),
('Daniel Oliveira', 'daniel@example.com', '44455566677', '11966644444', '1988-09-05', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0),
('Elisa Maria Rocha', 'elisa@example.com', '55566677788', '11955555555', '1992-11-18', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0),
('Fernando Alves', 'fernando@example.com', '66677788899', '11944466666', '1980-01-30', '$2y$10$kTn/2vwfVsajjEu8yhix7el4XCwFGsRz1j1fx//R3JMTZ9HPUL3hS', 1, 0);

---------------------------------------------
-- 2. MÉDICOS (um para cada especialidade)
---------------------------------------------
-- id_especialidade: 1=Cardiologia, 2=Dermatologia, 3=Oftalmologia,
--                    4=Odontologia, 5=Pneumologia, 6=Gastroenterologia
INSERT INTO medicos (nome, crm, id_especialidade, email, telefone, valor_consulta, percentual_medico, percentual_exame, bio, ativo) VALUES
('Dr. Ricardo Lopes', 'CRM-SP 12345', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'), 'ricardo.lopes@clinica.com', '11988888888', 250.00, 70.00, 50.00, 'Cardiologista formado pela USP com 15 anos de experiência em hemodinâmica.', 1),
('Dra. Patrícia Menezes', 'CRM-SP 23456', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'), 'patricia.menezes@clinica.com', '11987777777', 220.00, 70.00, 50.00, 'Especialista em dermatologia clínica e estética, membro da SBD.', 1),
('Dr. Augusto Cesar', 'CRM-SP 34567', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'), 'augusto.cesar@clinica.com', '11986666666', 200.00, 70.00, 50.00, 'Oftalmologista com ênfase em cirurgias refrativas e catarata.', 1),
('Dra. Leticia Santos', 'CRM-SP 45678', (SELECT id FROM especialidades WHERE nome = 'Odontologia'), 'leticia.santos@clinica.com', '11985555555', 180.00, 70.00, 50.00, 'Odontopediatria e ortodontia, atendimento humanizado.', 1),
('Dr. Marcos Paulo', 'CRM-SP 56789', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'), 'marcos.paulo@clinica.com', '11984444444', 230.00, 70.00, 50.00, 'Pneumologista, especialista em DPOC e asma.', 1),
('Dra. Fernanda Lima', 'CRM-SP 67890', (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia'), 'fernanda.lima@clinica.com', '11983333333', 210.00, 70.00, 50.00, 'Gastroenterologista, endoscopia digestiva e hepatologia.', 1);

---------------------------------------------
-- 3. HORÁRIOS DE ATENDIMENTO (cada médico)
---------------------------------------------
-- Dias: 0=domingo, 1=segunda, 2=terça, 3=quarta, 4=quinta, 5=sexta, 6=sábado
-- Intervalo padrão 30 minutos

-- Dr. Ricardo (Cardio)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), 1, '09:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), 3, '09:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), 5, '14:00', '18:00', 30, 1);

-- Dra. Patrícia (Dermato)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), 2, '10:00', '13:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), 4, '10:00', '13:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), 6, '08:00', '12:00', 30, 1);

-- Dr. Augusto (Oftalmo)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), 1, '14:00', '17:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), 3, '14:00', '17:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), 5, '08:00', '12:00', 30, 1);

-- Dra. Leticia (Odonto)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), 2, '08:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), 4, '08:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), 6, '09:00', '13:00', 30, 1);

-- Dr. Marcos (Pneumo)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), 1, '09:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), 3, '09:00', '12:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), 5, '14:00', '18:00', 30, 1);

-- Dra. Fernanda (Gastro)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), 2, '14:00', '18:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), 4, '14:00', '18:00', 30, 1),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 67890'), 6, '08:00', '12:00', 30, 1);

---------------------------------------------
-- 4. AGENDAMENTOS DE EXEMPLO
---------------------------------------------
-- valor_total = valor_consulta do médico
-- valor_medico = valor_total * (percentual_medico / 100)
-- status: pendente, confirmado, cancelado, concluído
-- status_pagamento: pendente, pago_clinica, pago_medico

-- Consulta concluída (já realizada) - Ana com Dr. Ricardo (Cardio), repasse já pago
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'ana@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Cardiologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-05-11 09:30:00', 'concluído', 'Paciente com pressão alta', 250.00, 175.00, 'pago_medico', '2026-05-11');

-- Consulta confirmada (futura) - Bruno com Dra. Patrícia (Dermato)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'bruno@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Dermatologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 23456'), date('now', '+5 days') || ' 10:30:00', 'confirmado', 'Mancha na pele', 220.00, 154.00, 'pendente', NULL);

-- Consulta pendente (futura) - Carla com Dr. Augusto (Oftalmo)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'carla@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Oftalmologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 34567'), date('now', '+10 days') || ' 15:00:00', 'pendente', 'Dificuldade de visão', 200.00, 140.00, 'pendente', NULL);

-- Consulta cancelada - Daniel com Dra. Leticia (Odonto)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'daniel@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Odontologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 45678'), date('now', '-2 days') || ' 11:00:00', 'cancelado', 'Cliente cancelou por motivo pessoal', 180.00, 126.00, 'pendente', NULL);

-- Exame solicitado - Elisa (sem médico associado)
INSERT INTO agendamentos (id_cliente, tipo, id_exame, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'elisa@example.com'), 'exame', (SELECT id FROM exames WHERE nome = 'Eletrocardiograma'), date('now', '+3 days') || ' 10:00:00', 'pendente', 'Solicitação de eletrocardiograma', 150.00, NULL, 'pendente', NULL);

-- Consulta concluída e já paga - Fernando com Dr. Marcos (Pneumo)
INSERT INTO agendamentos (id_cliente, tipo, id_especialidade, id_medico, data_hora, status, notas, valor_total, valor_medico, status_pagamento, data_realizacao) VALUES
((SELECT id FROM clientes WHERE email = 'fernando@example.com'), 'consulta', (SELECT id FROM especialidades WHERE nome = 'Pneumologia'), (SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-05-15 09:00:00', 'concluído', 'Tosse seca há semanas', 230.00, 161.00, 'pago_medico', '2026-05-15');

---------------------------------------------
-- 5. PAGAMENTOS REALIZADOS AOS MÉDICOS (exemplo)
---------------------------------------------
INSERT INTO pagamentos_medicos (id_medico, periodo_inicio, periodo_fim, total_consultas, valor_total_recebido, valor_repassado, data_pagamento, status, observacoes) VALUES
((SELECT id FROM medicos WHERE crm = 'CRM-SP 12345'), '2026-05-01', '2026-05-31', 1, 250.00, 175.00, '2026-06-05', 'pago', 'Pagamento referente a consultas de maio'),
((SELECT id FROM medicos WHERE crm = 'CRM-SP 56789'), '2026-05-01', '2026-05-31', 1, 230.00, 161.00, '2026-06-05', 'pago', 'Pagamento Dr. Marcos - maio');

-- Fim do script
