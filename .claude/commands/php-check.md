Audita o código PHP em busca de padrões potencialmente inseguros: saídas sem sanitização, queries sem prepared statements, e inclusões dinâmicas inseguras.

## O que fazer

Use o Grep tool (não Bash) para cada verificação abaixo. Reporte os resultados agrupados por categoria.

### 1. Outputs sem `htmlspecialchars` (risco de XSS)
Procure por `<?=` ou `echo` diretamente com variáveis `$_GET`, `$_POST`, `$_SESSION`, `$row[`, `$resultado[`:
```
pattern: echo\s+\$_(GET|POST|SESSION|REQUEST)|<?=\s*\$_(GET|POST|SESSION|REQUEST)
glob: **/*.php
```

### 2. Queries SQL com concatenação (risco de SQL Injection)
Procure por strings SQL com variáveis concatenadas:
```
pattern: (SELECT|INSERT|UPDATE|DELETE).*\.\s*\$
glob: **/*.php
```

### 3. Includes/requires com variáveis (risco de path traversal)
```
pattern: (include|require)(_once)?\s*\(\s*\$
glob: **/*.php
```

### 4. Senhas em texto plano (sem hash)
```
pattern: password\s*=\s*['"](?!.*hash)
glob: **/*.php
```

### 5. `$_GET`/`$_POST` usados sem sanitização prévia
```
pattern: \$_(GET|POST|REQUEST)\[.*\](?!\s*[;,)])
glob: **/*.php
```

## Como reportar

Para cada categoria:
- Se não encontrar nada: "✓ Sem ocorrências"
- Se encontrar: listar arquivo:linha com o trecho, e indicar se é falso positivo provável ou risco real

## Notas
- Este projeto usa `sanitizar_input()` e `htmlspecialchars()` como padrão — ocorrências devem ser exceções legítimas ou bugs
- Prepared statements com `bind_param()` são o padrão correto — queries com concatenação de `$` são suspeitas
- Falsos positivos comuns: comentários PHP, strings de SQL dentro de `echo` para debug
