-- ====================================================
-- SCRIPT: Migração - Médicos, Horários e Financeiro
-- Banco: database/clinica.sqlite
--
-- Este script é seguro para rodar em um banco já existente
-- (criado pela versão anterior do schema, sem as tabelas de
-- médicos). Ele NÃO apaga dados existentes.
--
-- Como executar:
--   sqlite3 database/clinica.sqlite < sql/migrate_medicos.sql
-- ou, dentro do shell do sqlite3:
--   .read sql/migrate_medicos.sql
--
-- Observação: a aplicação também aplica esta migração
-- automaticamente na primeira requisição após a atualização
-- (ver backend/config/conexao.php), portanto rodar manualmente
-- é opcional.
-- ====================================================

PRAGMA foreign_keys = ON;

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
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_especialidade) REFERENCES especialidades(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_medicos_especialidade ON medicos(id_especialidade);
CREATE INDEX IF NOT EXISTS idx_medicos_crm ON medicos(crm);

-- Tabela: horarios_atendimento
CREATE TABLE IF NOT EXISTS horarios_atendimento (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_medico INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL CHECK(dia_semana BETWEEN 0 AND 6),
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    intervalo_minutos INTEGER DEFAULT 30,
    ativo INTEGER DEFAULT 1,
    FOREIGN KEY (id_medico) REFERENCES medicos(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_horarios_medico ON horarios_atendimento(id_medico);
CREATE INDEX IF NOT EXISTS idx_horarios_dia ON horarios_atendimento(dia_semana);

-- Tabela: pagamentos_medicos
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

-- Novas colunas em agendamentos
-- (cada ALTER TABLE falha se a coluna já existir; rode apenas
--  os blocos necessários caso execute manualmente várias vezes)
ALTER TABLE agendamentos ADD COLUMN id_medico INTEGER REFERENCES medicos(id);
ALTER TABLE agendamentos ADD COLUMN valor_total DECIMAL(10,2);
ALTER TABLE agendamentos ADD COLUMN valor_medico DECIMAL(10,2);
ALTER TABLE agendamentos ADD COLUMN status_pagamento VARCHAR(20) NOT NULL DEFAULT 'pendente';
ALTER TABLE agendamentos ADD COLUMN data_realizacao DATE;

CREATE INDEX IF NOT EXISTS idx_agendamentos_medico ON agendamentos(id_medico);

-- ====================================================
-- FIM DA MIGRAÇÃO
-- ====================================================
