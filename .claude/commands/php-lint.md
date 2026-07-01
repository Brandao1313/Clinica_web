Verifica a sintaxe PHP de todos os arquivos .php do projeto e reporta erros encontrados.

## O que fazer

1. Execute o lint em todos os arquivos PHP recursivamente:
   ```powershell
   $erros = @()
   $arquivos = Get-ChildItem -Recurse -Filter "*.php" -Exclude "vendor" | Where-Object { $_.FullName -notmatch '\\vendor\\' }
   foreach ($arquivo in $arquivos) {
       $resultado = & php -l $arquivo.FullName 2>&1
       if ($LASTEXITCODE -ne 0) {
           $erros += $resultado
       }
   }
   if ($erros.Count -eq 0) {
       Write-Host "OK: Nenhum erro de sintaxe encontrado em $($arquivos.Count) arquivos PHP."
   } else {
       Write-Host "ERROS encontrados:"
       $erros | ForEach-Object { Write-Host $_ }
   }
   ```

2. Reporte ao usuário:
   - Número total de arquivos verificados
   - Lista de arquivos com erro (se houver) com linha e descrição do erro
   - Confirmação "OK: nenhum erro" se tudo estiver correto

## Notas
- Apenas verifica sintaxe — não executa o código
- Erros comuns: `<?php` esquecido, chaves sem fechar, ponto-e-vírgula faltando
- Não substitui análise estática completa (PHPStan, Psalm)
