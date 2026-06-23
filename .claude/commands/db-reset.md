Reset o banco de dados SQLite do projeto, apagando e recriando do zero com os dados iniciais do schema.

## O que fazer

1. Confirme com o usuário antes de prosseguir — esta operação apaga TODOS os dados.

2. Delete o arquivo do banco de dados:
   ```powershell
   Remove-Item -Force "database/clinica.sqlite" -ErrorAction SilentlyContinue
   ```

3. Recrie o banco executando o script PHP que instancia a conexão (isso aciona o auto-create via `schema.sqlite.sql`):
   ```powershell
   php -r "define('BASE_PATH', realpath('.')); require_once 'backend/config/conexao.php'; echo 'Banco recriado com sucesso.' . PHP_EOL;"
   ```

4. Confirme que o arquivo foi criado:
   ```powershell
   if (Test-Path "database/clinica.sqlite") { Write-Host "OK: database/clinica.sqlite criado." } else { Write-Host "ERRO: arquivo não encontrado." }
   ```

5. Informe ao usuário que o banco foi resetado e que os dados iniciais (seed) já estão incluídos no schema — admin@clinica.com / admin123.

## Notas
- O schema completo está em `sql/schema.sqlite.sql`
- Os dados de seed (especialidades, exames, admin, médicos) são inseridos automaticamente pela classe `Conexao`
- Para adicionar dados de teste adicionais após o reset, use `/db-seed`
