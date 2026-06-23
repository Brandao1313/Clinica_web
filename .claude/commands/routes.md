Lista todos os endpoints e ações disponíveis no projeto: páginas públicas, controladores POST, APIs JSON e ações dos painéis.

## O que fazer

### 1. Páginas públicas (raiz do projeto)
Use Glob para listar arquivos `.php` na raiz:
```
pattern: *.php
```
Para cada arquivo encontrado, leia as primeiras 10 linhas para confirmar se é uma página pública ou redirecionamento.

### 2. Controladores POST (`backend/controllers/`)
Use Grep para encontrar os valores de `?acao=` processados em cada controller:
```
pattern: \$_POST\['acao'\]|case '|acao ==
path: backend/controllers/
glob: **/*.php
```
Liste: arquivo → ações suportadas.

### 3. Ações dos painéis (`backend/views/`)
Use Grep para encontrar os `switch/case` ou `if ($acao ===` em cada painel:
```
pattern: case '[\w_]+':|acao === '[\w_]+'
path: backend/views/
glob: **/*.php
```
Liste: painel → ações → tipo de usuário requerido.

### 4. APIs JSON (retornam JSON)
Use Grep para identificar arquivos que fazem `header('Content-Type: application/json')`:
```
pattern: Content-Type.*application/json
glob: **/*.php
```

## Formato de saída esperado

```
## Páginas Públicas
GET /index.php                      — Homepage
GET /especialidades.php             — Lista especialidades
GET /exames.php                     — Lista exames
GET /medicos.php                    — Lista médicos
...

## Autenticação
POST /backend/auth/logar.php        — Login (rate limiting: 5 tentativas/15min)
POST /backend/auth/registrar.php    — Cadastro
...

## APIs JSON
GET /backend/controllers/medicos_por_especialidade.php?id_especialidade=N
GET /backend/controllers/horarios_disponiveis.php?id_medico=N&data=YYYY-MM-DD

## Painel Cliente (require_cliente)
GET /backend/views/painel_cliente.php?acao=dashboard
GET /backend/views/painel_cliente.php?acao=agendamentos
...

## Painel Admin (require_admin)
...
```
