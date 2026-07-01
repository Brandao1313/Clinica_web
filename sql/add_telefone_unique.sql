-- ====================================================
-- SCRIPT: Tornar telefone obrigatório e único (clientes)
-- ====================================================

-- Remover possíveis NULLs/vazios antes de aplicar a constraint
UPDATE clientes SET telefone = '0000000' || id WHERE telefone IS NULL OR telefone = '';

-- Criar índice único (impede duplicatas a partir de agora)
CREATE UNIQUE INDEX IF NOT EXISTS idx_clientes_telefone ON clientes(telefone);
