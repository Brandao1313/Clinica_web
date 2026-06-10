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
    telefone VARCHAR(15),
    data_nascimento DATE,
    senha_hash VARCHAR(255) NOT NULL,
    ativo INTEGER DEFAULT 1,
    eh_admin INTEGER DEFAULT 0,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);
CREATE INDEX IF NOT EXISTS idx_clientes_cpf ON clientes(cpf);

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

-- Tabela: agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER NOT NULL,
    tipo TEXT NOT NULL CHECK(tipo IN ('consulta', 'exame')),
    id_especialidade INTEGER,
    id_exame INTEGER,
    data_hora DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente' CHECK(status IN ('pendente', 'confirmado', 'cancelado', 'concluído')),
    notas TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_especialidade) REFERENCES especialidades(id) ON DELETE SET NULL,
    FOREIGN KEY (id_exame) REFERENCES exames(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_agendamentos_cliente ON agendamentos(id_cliente);
CREATE INDEX IF NOT EXISTS idx_agendamentos_data ON agendamentos(data_hora);
CREATE INDEX IF NOT EXISTS idx_agendamentos_status ON agendamentos(status);

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

-- ====================================================
-- FIM DO SCRIPT
-- ====================================================
