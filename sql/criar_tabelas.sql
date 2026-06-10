-- ====================================================
-- SCRIPT: Criar tabelas e dados iniciais para Clínica
-- Banco: clinica_db
-- ====================================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS clinica_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica_db;

-- Tabela: clientes (usuários do sistema)
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    telefone VARCHAR(15),
    data_nascimento DATE,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    eh_admin TINYINT(1) DEFAULT 0,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: especialidades
CREATE TABLE IF NOT EXISTS especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: exames
CREATE TABLE IF NOT EXISTS exames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) DEFAULT 0.00,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    tipo ENUM('consulta', 'exame') NOT NULL,
    id_especialidade INT,
    id_exame INT,
    data_hora DATETIME NOT NULL,
    status ENUM('pendente', 'confirmado', 'cancelado', 'concluído') DEFAULT 'pendente',
    notas TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_especialidade) REFERENCES especialidades(id) ON DELETE SET NULL,
    FOREIGN KEY (id_exame) REFERENCES exames(id) ON DELETE SET NULL,
    INDEX(id_cliente),
    INDEX(data_hora),
    INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: redefinicao_senha (para reset password)
CREATE TABLE IF NOT EXISTS redefinicao_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    data_expiracao DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX(token),
    INDEX(data_expiracao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('Administrador', 'admin@clinica.com', '00000000000', '1133334444', '1990-01-01', '$2y$10$YIjlrPNoS0jBBW5.vQvT.OPST9/PgBkqParsTQQvVceuK72XKeHm2', 1, 1);
-- Nota: a senha hash acima corresponde a "admin123"

-- Cliente de teste
INSERT INTO clientes (nome, email, cpf, telefone, data_nascimento, senha_hash, ativo, eh_admin) VALUES
('João da Silva', 'joao@example.com', '12345678901', '1133335555', '1985-05-15', '$2y$10$YIjlrPNoS0jBBW5.vQvT.OPST9/PgBkqParsTQQvVceuK72XKeHm2', 1, 0);
-- Nota: a senha hash acima corresponde a "admin123" (para teste fácil)

-- ====================================================
-- FIM DO SCRIPT
-- ====================================================
