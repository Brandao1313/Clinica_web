Exibe a contagem de registros em cada tabela do banco de dados SQLite, útil para verificar o estado do banco após seeds, resets ou operações em massa.

## O que fazer

1. Verifique se o banco existe:
   ```powershell
   if (-not (Test-Path "database/clinica.sqlite")) {
       Write-Host "ERRO: database/clinica.sqlite não encontrado. Execute /db-reset primeiro."
       exit
   }
   ```

2. Execute o script PHP para contar registros em cada tabela:
   ```powershell
   php -r "
   define('BASE_PATH', realpath('.'));
   require_once 'backend/config/conexao.php';
   \$db = Conexao::getInstance()->getConexao();
   \$tabelas = ['clientes', 'especialidades', 'exames', 'medicos', 'horarios_atendimento', 'agendamentos', 'pagamentos_medicos', 'redefinicao_senha'];
   echo PHP_EOL;
   echo str_pad('Tabela', 30) . str_pad('Registros', 12) . PHP_EOL;
   echo str_repeat('-', 42) . PHP_EOL;
   foreach (\$tabelas as \$tabela) {
       try {
           \$stmt = \$db->prepare('SELECT COUNT(*) as total FROM ' . \$tabela);
           \$stmt->execute();
           \$row = \$stmt->get_result()->fetch_assoc();
           echo str_pad(\$tabela, 30) . str_pad(\$row['total'], 12) . PHP_EOL;
       } catch (Exception \$e) {
           echo str_pad(\$tabela, 30) . 'ERRO: ' . \$e->getMessage() . PHP_EOL;
       }
   }
   echo PHP_EOL;
   "
   ```

3. Apresente os resultados ao usuário de forma clara, destacando se alguma tabela está vazia quando não deveria estar.

## Saída esperada

```
Tabela                         Registros
------------------------------------------
clientes                       4
especialidades                 6
exames                         6
medicos                        3
horarios_atendimento           7
agendamentos                   12
pagamentos_medicos             0
redefinicao_senha              0
```

## Notas
- `clientes` deve ter ao menos 2 registros (admin + cliente teste) após `/db-reset`
- `especialidades` e `exames` devem ter 6 cada (seed do schema)
- `medicos` deve ter 3 (Dra. Ana, Dr. Carlos, Dra. Beatriz)
- `agendamentos` e `pagamentos_medicos` ficam vazios após reset puro — use `/db-seed` para popular
