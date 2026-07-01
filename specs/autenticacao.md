# Spec: Autenticação & Usuários

**Módulo:** `backend/auth/` + `cadastro/`  
**Status geral:** ✅ Implementado

---

## Visão Geral

O sistema possui uma única tabela `clientes` que comporta todos os tipos de usuário. O campo `tipo` determina as permissões e qual painel o usuário acessa após o login.

---

## Tipos de Usuário

| Tipo | Painel | Guard PHP | Criado por |
|------|--------|-----------|-----------|
| `cliente` | `painel_cliente.php` | `require_cliente()` | Auto-cadastro público |
| `admin` | `painel_admin.php` | `require_admin()` | Seed do banco (ID=1) |
| `medico` | `painel_medico.php` | `require_medico()` | Admin via CRUD de médicos |
| `recepcionista` | `painel_recepcionista.php` | `require_recepcionista()` | Admin via banco direto |

---

## RF-AUTH-01: Cadastro de Clientes

**Arquivo:** `backend/auth/registrar.php`  
**Trigger:** POST em `cadastro/criar_conta.php`

### Campos obrigatórios

| Campo | Validação |
|-------|-----------|
| Nome | Mínimo 3 chars, apenas letras/espaços/hífens/apóstrofos (aceita acentos) |
| Email | Formato válido via `FILTER_VALIDATE_EMAIL` + unicidade no banco |
| CPF | 11 dígitos, dígitos verificadores mod-11 + unicidade no banco |
| Telefone | 10–11 dígitos numéricos (DDD + número) + unicidade no banco |
| Data de nascimento | Formato `YYYY-MM-DD` + idade mínima 18 anos |
| Senha | Mínimo `PASSWORD_MIN_LENGTH` (6) caracteres |

### Regras de negócio

- Email, CPF e telefone são únicos no sistema (qualquer tentativa de duplicata retorna erro específico por campo)
- Tipo padrão: `'cliente'`
- Senha armazenada como `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10])`
- Após cadastro bem-sucedido: flash message de sucesso + redirect para login

### Lacunas / Melhorias

- ⚠️ **Senha fraca**: mínimo 6 chars sem requisitos de complexidade — considerar aumentar para 8 e exigir ao menos 1 número
- ⚠️ **Sem máscara de CPF**: input recebe 11 dígitos sem formatação — considerar máscara JS `000.000.000-00`
- ⚠️ **Sem verificação de email**: cadastro não exige confirmação de email — mensagens de erro de login não diferenciam "email não encontrado" de "email não confirmado"
- ❌ **Sem criação de recepcionista via UI**: recepcionistas precisam ser inseridos diretamente no banco ou via admin que não tem essa opção visível

---

## RF-AUTH-02: Login

**Arquivo:** `backend/auth/logar.php`  
**Trigger:** POST em `cadastro/login.php`

### Fluxo

1. Validar CSRF token
2. Sanitizar email e senha recebidos
3. Buscar usuário por email com prepared statement
4. Verificar `ativo = 1`
5. `password_verify($senha, $hash)` — erro genérico "Email ou senha incorretos" em qualquer falha (evita user enumeration)
6. Verificar rate limiting (máx 5 tentativas / 15 min por IP via `$_SESSION`)
7. Criar variáveis de sessão
8. Se tipo = `medico`: buscar `id_medico` na tabela `medicos` via `id_cliente`
9. Redirect para painel correspondente ao tipo

### Sessão criada

```php
$_SESSION['id_cliente']    // int
$_SESSION['nome_cliente']  // string
$_SESSION['email_cliente'] // string
$_SESSION['tipo_usuario']  // 'admin' | 'medico' | 'recepcionista' | 'cliente'
$_SESSION['id_medico']     // int | null (apenas para tipo='medico')
$_SESSION['eh_admin']      // bool (compat)
$_SESSION['eh_recepcionista'] // bool (compat)
$_SESSION['eh_medico']     // bool (compat)
```

### Regras de negócio

- Usuário inativo (`ativo = 0`) não consegue logar — mesma mensagem genérica de erro
- Após 5 tentativas falhas no mesmo IP: bloqueio de 15 minutos, registrado em `logs/security.log`
- Session ID regenerado imediatamente após login
- Session ID regenerado a cada 5 minutos de atividade

### Lacunas / Melhorias

- ⚠️ **Rate limiting por IP, não por email**: atacante pode alternar IPs. Considerar combinar IP + email
- ⚠️ **`SESSION_TIMEOUT` (3600s) não é aplicado ativamente**: a constante existe mas não há verificação de expiração de sessão idle no middleware
- ❌ **Sem "lembrar de mim"**: sem opção de sessão persistente via cookie

---

## RF-AUTH-03: Logout

**Arquivo:** `backend/auth/deslogar.php`  
**Trigger:** GET/link de logout no header

### Fluxo

1. Registra `log_acao('LOGOUT', ...)` antes de destruir sessão
2. `session_destroy()`
3. Flash message de sucesso
4. Redirect para login

---

## RF-AUTH-04: Recuperação de Senha

**Arquivo:** `backend/auth/redefinir_senha.php`  
**Trigger:** POST em `cadastro/esqueci_senha.php`

### Fluxo (2 etapas)

**Etapa 1 — Verificar identidade por telefone + CPF:**
1. Validar CSRF
2. Rate limiting: máx 3 tentativas de reset por hora por telefone
3. Buscar cliente por telefone + CPF
4. Se encontrado: salvar `$_SESSION['id_reset_autorizado']` com expiração de 5 minutos
5. Redirecionar para formulário de nova senha

**Etapa 2 — Redefinir senha:**
1. Validar CSRF
2. Verificar `$_SESSION['id_reset_autorizado']` ainda válido (< 5 min)
3. Validar nova senha (mínimo 6 chars)
4. `password_hash()` com bcrypt
5. Atualizar `senha_hash` na tabela `clientes`
6. Destruir `$_SESSION['id_reset_autorizado']`
7. Flash message + redirect para login

### Lacunas / Melhorias

- ⚠️ **Sem envio real de email**: reset por telefone+CPF é menos seguro que link por email. Log em `logs/emails.log` simula o envio mas não envia de fato
- ⚠️ **Tabela `redefinicao_senha`** existe com tokens mas não está sendo utilizada — o fluxo atual usa sessão em memória
- ❌ **Sem expiração forçada de outras sessões ativas** após reset de senha

---

## RF-AUTH-05: Controle de Acesso (Guards)

**Arquivo:** `backend/utils/seguranca.php`

| Função | Comportamento |
|--------|--------------|
| `require_login()` | Redireciona para `cadastro/login.php` se não autenticado |
| `require_admin()` | Redireciona para `index.php` se não for admin |
| `require_medico()` | Redireciona para `index.php` se não for médico |
| `require_recepcionista()` | Redireciona para `index.php` se não for recepcionista |
| `require_cliente()` | Redireciona tipos especiais para o painel correto |

### Regras de negócio

- Sub-views do admin (`especialidade_form.php`, `medico_listar.php`, etc.) verificam a constante `PAINEL_ADMIN_LOADED` para impedir acesso direto por URL
- Todos os controllers verificam autenticação antes de processar qualquer dado

---

## Critérios de Aceitação Gerais

- [ ] Usuário não autenticado não consegue acessar nenhum painel via URL direta
- [ ] Admin não consegue acessar painel de médico e vice-versa
- [ ] CPF duplicado retorna erro específico "CPF já cadastrado"
- [ ] Email duplicado retorna erro específico "Email já cadastrado"
- [ ] 6 tentativas de login com senha errada resultam em bloqueio de 15 min
- [ ] Após logout, sessão anterior não funciona mais
- [ ] Reset de senha: usuário com dados errados (telefone/CPF) não recebe acesso
