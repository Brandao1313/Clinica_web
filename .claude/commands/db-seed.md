Popula o banco de dados SQLite com dados de teste realistas (pacientes, agendamentos, histórico).

## O que fazer

1. Verifique se o banco existe:
   ```powershell
   if (-not (Test-Path "database/clinica.sqlite")) { Write-Host "ERRO: banco não encontrado. Execute /db-reset primeiro." }
   ```

2. Execute o script de seed via PHP com PDO SQLite:
   ```powershell
   php -r "
   define('BASE_PATH', realpath('.'));
   require_once 'backend/config/conexao.php';
   \$db = Conexao::getInstance()->getConexao();
   \$sql = file_get_contents('sql/populate_test_data.sql');
   \$db->exec(\$sql);
   echo 'Seed executado com sucesso.' . PHP_EOL;
   "
   ```

3. Verifique o resultado com `/db-status` para confirmar as contagens de registros.

## Notas
- O arquivo de seed é `sql/populate_test_data.sql`
- O seed pode falhar com UNIQUE constraint se os dados já existirem — use `/db-reset` antes se necessário
- Após o seed, os dados de teste estão disponíveis para testar todas as funcionalidades do sistema
