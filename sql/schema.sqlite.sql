-- ====================================================
-- SCRIPT: Criar tabelas e dados iniciais (SQLite)
-- Banco: database/clinica.sqlite
-- ====================================================

PRAGMA foreign_keys = ON;

-- Tabela: clientes (usuários do sistema)
CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    telefone VARCHAR(15) UNIQUE NOT NULL,
    data_nascimento DATE,
    senha_hash VARCHAR(255) NOT NULL,
    ativo INTEGER DEFAULT 1,
    eh_admin INTEGER DEFAULT 0,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);
CREATE INDEX IF NOT EXISTS idx_clientes_cpf ON clientes(cpf);
CREATE UNIQUE INDEX IF NOT EXISTS idx_clientes_telefone ON clientes(telefone);

-- Tabela: especialidades
CREATE TABLE IF NOT EXISTS especialidades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo INTEGER DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela: exames
CREATE TABLE IF NOT EXISTS exames (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) DEFAULT 0.00,
    ativo INTEGER DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela: medicos
CREATE TABLE IF NOT EXISTS medicos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(150) NOT NULL,
    crm VARCHAR(20) UNIQUE NOT NULL,
    id_especialidade INTEGER NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(15),
    valor_consulta DECIMAL(10,2) NOT NULL,
    percentual_medico DECIMAL(5,2) DEFAULT 70.00,
    percentual_exame DECIMAL(5,2) DEFAULT 0.00,
    bio TEXT,
    foto VARCHAR(255),
    ativo INTEGER DEFAULT 1,
    senha_hash VARCHAR(255),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_especialidade) REFERENCES especialidades(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_medicos_especialidade ON medicos(id_especialidade);
CREATE INDEX IF NOT EXISTS idx_medicos_crm ON medicos(crm);

-- Tabela: horarios_atendimento
CREATE TABLE IF NOT EXISTS horarios_atendimento (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_medico INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL CHECK(dia_semana BETWEEN 0 AND 6), -- 0=domingo a 6=sábado
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    intervalo_minutos INTEGER DEFAULT 30,
    ativo INTEGER DEFAULT 1,
    FOREIGN KEY (id_medico) REFERENCES medicos(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_horarios_medico ON horarios_atendimento(id_medico);
CREATE INDEX IF NOT EXISTS idx_horarios_dia ON horarios_atendimento(dia_semana);

-- Tabela: agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER NOT NULL,
    tipo TEXT NOT NULL CHECK(tipo IN ('consulta', 'exame')),
    id_especialidade INTEGER,
    id_exame INTEGER,
    id_medico INTEGER,
    data_hora DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente' CHECK(status IN ('pendente', 'confirmado', 'cancelado', 'concluído')),
    notas TEXT,
    valor_total DECIMAL(10,2),
    valor_medico DECIMAL(10,2),
    status_pagamento VARCHAR(20) NOT NULL DEFAULT 'pendente' CHECK(status_pagamento IN ('pendente', 'pago_clinica', 'pago_medico')),
    data_realizacao DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_especialidade) REFERENCES especialidades(id) ON DELETE SET NULL,
    FOREIGN KEY (id_exame) REFERENCES exames(id) ON DELETE SET NULL,
    FOREIGN KEY (id_medico) REFERENCES medicos(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_agendamentos_cliente ON agendamentos(id_cliente);
CREATE INDEX IF NOT EXISTS idx_agendamentos_data ON agendamentos(data_hora);
CREATE INDEX IF NOT EXISTS idx_agendamentos_status ON agendamentos(status);
CREATE INDEX IF NOT EXISTS idx_agendamentos_medico ON agendamentos(id_medico);

-- Tabela: pagamentos_medicos (controle de repasses)
CREATE TABLE IF NOT EXISTS pagamentos_medicos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_medico INTEGER NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fim DATE NOT NULL,
    total_consultas INTEGER NOT NULL,
    valor_total_recebido DECIMAL(10,2) NOT NULL,
    valor_repassado DECIMAL(10,2) NOT NULL,
    data_pagamento DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'pendente' CHECK(status IN ('pendente', 'pago')),
    observacoes TEXT,
    FOREIGN KEY (id_medico) REFERENCES medicos(id)
);

CREATE INDEX IF NOT EXISTS idx_pagamentos_medico ON pagamentos_medicos(id_medico);

-- Tabela: redefinicao_senha (para reset de senha)
CREATE TABLE IF NOT EXISTS redefinicao_senha (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    data_expiracao DATETIME NOT NULL,
    usado INTEGER DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_redefinicao_token ON redefinicao_senha(token);
CREATE INDEX IF NOT EXISTS idx_redefinicao_expiracao ON redefinicao_senha(data_expiracao);

-- ====================================================
-- DADOS INICIAIS (teste)
-- ====================================================

-- Especialidades
INSERT INTO especialidades (nome, descricao) VALUES
('Cardiologia', 'Especialidade de doenças do coração e sistema cardiovascular'),
('Dermatologia', 'Especialidade de doenças de pele'),
('Oftalmologia', 'Especialidade de doenças dos olhos'),
('Odontologia', 'Especialidade de saúde bucal'),
('Pneumologia', 'Especialidade de doenças respiratórias'),
('Gastroenterologia', 'Especialidade de doenças do sistema digestivo');

-- Exames
INSERT INTO exames (nome, descricao, preco) VALUES
('Eletrocardiograma', 'Exame do coração', 150.00),
('Ultrassom Abdominal', 'Ultrassom da região abdominal', 200.00),
('Ressonância Magnética', 'Ressonância Magnética em geral', 800.00),
('Tomografia Computadorizada', 'Tomografia CT', 600.00),
('Hemograma Completo', 'Análise completa de sangue', 80.00),
('Teste de Diabetes', 'Verificação de glicemia', 50.00);

-- Admin (senha: admin123 - gerada com password_hash())
INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, ativo, eh_admin) VALUES
('Administrador', 'admin@clinica.com', '00000000000', '1133334444', '1990-01-01', '$2y$10$ONWeTMeUTXZAudfgnY7EcOUfLBXSOr7aEHZp3I3LmzYhWbi9KfMvm', 1, 1);
-- Nota: a senha hash acima corresponde a "admin123"

-- Cliente de teste
INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, ativo, eh_admin) VALUES
('João da Silva', 'joao@example.com', '12345678901', '1133335555', '1985-05-15', '$2y$10$ONWeTMeUTXZAudfgnY7EcOUfLBXSOr7aEHZp3I3LmzYhWbi9KfMvm', 1, 0);
-- Nota: a senha hash acima corresponde a "admin123" (para teste fácil)

-- Médicos de teste
INSERT INTO medicos (nome, crm, id_especialidade, email, telefone, valor_consulta, percentual_medico, percentual_exame, bio, ativo) VALUES
('Dra. Ana Souza', 'CRM-12345', 1, 'ana.souza@clinica.com', '1133336666', 200.00, 70.00, 0.00, 'Cardiologista com 15 anos de experiência em prevenção e tratamento de doenças cardiovasculares.', 1),
('Dr. Carlos Lima', 'CRM-23456', 2, 'carlos.lima@clinica.com', '1133337777', 180.00, 65.00, 0.00, 'Dermatologista especializado em dermatologia clínica e estética.', 1),
('Dra. Beatriz Rocha', 'CRM-34567', 3, 'beatriz.rocha@clinica.com', '1133338888', 220.00, 70.00, 0.00, 'Oftalmologista com foco em cirurgias refrativas e doenças da retina.', 1);

-- Horários de atendimento (0=domingo ... 6=sábado)
INSERT INTO horarios_atendimento (id_medico, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo) VALUES
(1, 1, '09:00', '12:00', 30, 1), -- Dra. Ana - segunda
(1, 3, '09:00', '12:00', 30, 1), -- Dra. Ana - quarta
(1, 5, '09:00', '12:00', 30, 1), -- Dra. Ana - sexta
(2, 2, '13:00', '17:00', 30, 1), -- Dr. Carlos - terça
(2, 4, '13:00', '17:00', 30, 1), -- Dr. Carlos - quinta
(3, 1, '14:00', '18:00', 30, 1), -- Dra. Beatriz - segunda
(3, 5, '08:00', '12:00', 30, 1); -- Dra. Beatriz - sexta

-- ====================================================
-- FIM DO SCRIPT
-- ====================================================
