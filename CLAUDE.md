# Clínica Saúde & Bem-Estar — CLAUDE.md

Guia de desenvolvimento para sessões do Claude Code neste projeto.

---

## Stack & Ambiente

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.0+ nativo (sem framework) |
| Banco | SQLite 3 via PDO (`pdo_sqlite`) |
| Servidor | Apache + XAMPP (Windows) |
| Frontend | CSS custom + JavaScript Vanilla |
| Ícones | Font Awesome 6.5.1 (CDN) |
| Fontes | Google Fonts — Poppins (títulos) + Inter (corpo) |

**URL local:** `http://localhost/Cinica_web`  
**Banco de dados:** `database/clinica.sqlite` (criado automaticamente na 1ª requisição)

---

## Estrutura do Projeto

```
Cinica_web/
├── index.php                        # Homepage pública
├── especialidades.php               # Listagem pública de especialidades
├── exames.php                       # Listagem pública de exames
├── medicos.php                      # Listagem pública de médicos
├── medico_detalhe.php               # Detalhe individual de médico
├── sobre.php                        # Página institucional
│
├── cadastro/
│   ├── login.php                    # Formulário de login
│   ├── criar_conta.php              # Formulário de cadastro
│   └── esqueci_senha.php            # Recuperação de senha
│
├── backend/
│   ├── config/
│   │   ├── config.php               # Constantes globais + session_start
│   │   └── conexao.php              # Singleton PDO/SQLite (auto-cria banco)
│   │
│   ├── auth/                        # POST handlers de autenticação
│   │   ├── logar.php                # Login com rate limiting
│   │   ├── registrar.php            # Cadastro com validações completas
│   │   ├── deslogar.php             # Logout + destruição de sessão
│   │   └── redefinir_senha.php      # Reset via telefone + CPF
│   │
│   ├── controllers/                 # Lógica de negócio (POST/GET handlers)
│   │   ├── agendamento_controller.php
│   │   ├── medico_controller.php
│   │   ├── especialidade_controller.php
│   │   ├── exame_controller.php
│   │   ├── financeiro_controller.php
│   │   ├── recepcionista_controller.php
│   │   ├── medico_perfil_controller.php
│   │   ├── horarios_disponiveis.php  # API JSON → GET ?id_medico&data
│   │   └── medicos_por_especialidade.php  # API JSON → GET ?id_especialidade
│   │
│   ├── utils/
│   │   ├── funcoes_gerais.php        # 65+ helpers de formatação, log, slots
│   │   ├── validacao.php             # CPF, email, telefone, data, senha
│   │   └── seguranca.php             # CSRF, hash, require_*, flash messages
│   │
│   ├── includes/
│   │   ├── header.php               # HTML head + navbar (detecta tipo de usuário)
│   │   └── footer.php               # Rodapé HTML
│   │
│   └── views/                       # Painéis autenticados (roteados por ?acao=)
│       ├── painel_cliente.php
│       ├── painel_admin.php
│       ├── painel_medico.php
│       ├── painel_recepcionista.php
│       ├── especialidade_listar.php / especialidade_form.php
│       ├── exame_listar.php / exame_form.php
│       ├── medico_listar.php / medico_form.php
│       ├── horarios_form.php
│       ├── financeiro.php
│       └── relatorios.php
│
├── assets/
│   ├── css/
│   │   ├── estilo.css               # Design system + variáveis CSS globais
│   │   └── components/              # CSS modular por componente
│   ├── js/
│   │   ├── app.js                   # Toasts, modais de confirmação
│   │   └── agendamento_horarios.js  # AJAX dinâmico de especialidade/médico/horário
│   └── imagens/
│
├── sql/
│   ├── schema.sqlite.sql            # Schema completo + seed de dados iniciais
│   ├── migrate_medicos.sql          # Migration incremental (tabela médicos)
│   └── populate_test_data.sql       # Dados de teste realistas
│
├── database/
│   └── clinica.sqlite               # Banco SQLite (ignorado no git)
│
└── logs/
    ├── atividades.log               # Ações: login, CRUD, agendamentos
    └── security.log                 # Eventos de segurança: tentativas, resets
```

---

## Arquitetura

**Padrão:** MVC simplificado sem router formal.

- **Roteamento:** parâmetro `?acao=` nos painéis (`painel_admin.php?acao=medicos`)
- **Models:** não há classes Model — lógica de dados fica nos controllers
- **Views:** arquivos PHP em `backend/views/` com HTML + PHP embutido
- **Controllers:** arquivos em `backend/controllers/` processam POST/GET e redirecionam

**Fluxo de requisição típico (POST):**
```
Form HTML → backend/controllers/xyz_controller.php
    → Validar CSRF
    → Verificar autenticação (require_login / require_admin)
    → Sanitizar inputs
    → Validar regras de negócio
    → Prepared statement PDO
    → log_acao()
    → set_flash_message()
    → redirect()
```

**Inicialização da aplicação:**
```
Qualquer página pública
    → require_once header.php
    → header.php require_once conexao.php + config.php + seguranca.php
    → conexao.php: Singleton PDO → auto-cria banco se não existir
    → config.php: constantes + session_start() + regeneração de ID
```

---

## Banco de Dados

**8 tabelas principais:**

| Tabela | Descrição |
|--------|-----------|
| `clientes` | Todos os usuários (tipo: cliente/admin/medico/recepcionista) |
| `especialidades` | Especialidades médicas |
| `exames` | Exames disponíveis com preço |
| `medicos` | Profissionais com CRM, especialidade, percentual de repasse |
| `horarios_atendimento` | Grade semanal por médico (dia 0-6, hora_inicio, hora_fim, intervalo_min) |
| `agendamentos` | Consultas e exames (status: pendente/confirmado/cancelado/concluído) |
| `pagamentos_medicos` | Repasses financeiros por período |
| `redefinicao_senha` | Tokens de recuperação com expiração |

**Conexão:**
```php
$db = Conexao::getInstance()->getConexao(); // Retorna objeto PDO-compatível
```

**Prepared statement (padrão obrigatório):**
```php
$stmt = $db->prepare("SELECT * FROM clientes WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
```

**Migrações:** Adicionar verificação em `Conexao::aplicarMigracoes()` + criar arquivo `sql/migrate_nome.sql`.

---

## Autenticação & Autorização

**Tipos de usuário** (campo `tipo` na tabela `clientes`):

| Tipo | Painel | Guard |
|------|--------|-------|
| `cliente` | `painel_cliente.php` | `require_cliente()` |
| `admin` | `painel_admin.php` | `require_admin()` |
| `medico` | `painel_medico.php` | `require_medico()` |
| `recepcionista` | `painel_recepcionista.php` | `require_recepcionista()` |

**Sessão após login:**
```php
$_SESSION['id_cliente']    // ID do usuário
$_SESSION['nome_cliente']  // Nome exibido
$_SESSION['email_cliente'] // Email
$_SESSION['tipo_usuario']  // 'admin' | 'medico' | 'recepcionista' | 'cliente'
$_SESSION['id_medico']     // Preenchido apenas para tipo='medico'
```

**Guards — sempre no topo dos controllers/views privadas:**
```php
require_once BASE_PATH . '/backend/utils/seguranca.php';
require_login();   // Redireciona para login se não autenticado
require_admin();   // Redireciona para index se não for admin
```

---

## Convenções de Código

- **Funções e variáveis:** `snake_case` (ex: `validar_cpf()`, `$id_cliente`)
- **Classes:** `CamelCase` (ex: `Conexao`, `StmtCompat`)
- **Constantes:** `UPPER_CASE` (ex: `SESSION_TIMEOUT`, `BASE_PATH`)
- **Includes:** sempre `require_once` com caminho absoluto via `BASE_PATH`
- **Comentários:** em português, separadores `// ====`
- **Idioma:** interface em português brasileiro

---

## Convenções de Git

### Formato do commit

```
<tipo>(<escopo>): <descrição>
```

- **Máximo 50 caracteres** na linha do título
- **Idioma:** português
- **Sem referências a ferramentas** de IA no commit (sem co-autor Claude Code)
- Um commit por funcionalidade — não agrupar mudanças não relacionadas

### Tipos semânticos

| Tipo | Quando usar |
|------|------------|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `style` | Alteração visual/CSS sem lógica |
| `refactor` | Reestruturação sem mudar comportamento |
| `docs` | Documentação (CLAUDE.md, specs, README) |
| `chore` | Configuração, dependências, arquivos de build |
| `test` | Testes ou dados de seed/teste |
| `db` | Migrations, schema, alterações no banco |

### Escopos comuns

`auth`, `agendamento`, `medico`, `admin`, `cliente`, `financeiro`, `recepcionista`, `publico`, `css`, `js`, `db`, `config`

### Exemplos corretos

```
feat(agendamento): cancelar consulta pelo cliente
fix(auth): corrigir rate limiting por IP
style(css): ajustar sidebar sticky no mobile
refactor(medico): extrair cálculo de repasse
docs(specs): adicionar spec de autenticação
db(medico): adicionar coluna foto_path
chore(config): mover includes para backend/
fix(admin): bloquear acesso direto a sub-views
feat(financeiro): gerar repasse por período
```

### Exemplos incorretos

```
# muito vago
atualização

# passa de 50 caracteres
feat(agendamento): implementar sistema completo de agendamento de consultas médicas

# em inglês
fix: fix login bug

# referência a ferramenta
feat: adicionar funcionalidade (gerado pelo Claude)
```

### Fluxo de branches

- `main` — produção estável
- `feat/<nome>` — nova funcionalidade (ex: `feat/upload-foto-medico`)
- `fix/<nome>` — correção de bug (ex: `fix/session-timeout`)
- `refactor/<nome>` — refatoração (ex: `refactor/sqlite-visual`)

---

## Padrões de Segurança (checklist obrigatório)

Toda nova feature deve seguir:

- [ ] **CSRF:** gerar token no form (`gerar_token_csrf()`), validar no controller (`validar_token_csrf($_POST['csrf_token'])`)
- [ ] **Autenticação:** `require_login()` + `require_admin()` etc. no topo do controller
- [ ] **SQL Injection:** sem concatenação em queries — usar `$db->prepare()` + `bind_param()`
- [ ] **XSS:** sanitizar inputs com `sanitizar_input($valor)` e outputs com `htmlspecialchars($valor)` ou `sanitizar_output($valor)`
- [ ] **Validação:** usar funções de `validacao.php` (CPF, email, telefone, data, senha)
- [ ] **Log:** registrar ação com `log_acao('ACAO', $id_usuario, 'detalhe')` após operações importantes

---

## Flash Messages

```php
// No controller (após operação):
set_flash_message('sucesso', 'Operação realizada com sucesso');
set_flash_message('erro', 'Ocorreu um erro');

// Na view (no topo do conteúdo):
$flash = get_flash_message('sucesso');
if ($flash): ?>
    <div class="flash-message flash-sucesso"><?= htmlspecialchars($flash) ?></div>
<?php endif;
```

Tipos disponíveis: `sucesso`, `erro`, `aviso`, `info`.

---

## Logging

```php
// Ações de negócio (logs/atividades.log):
log_acao('LOGIN', $id_cliente, 'Login realizado: ' . $email);
log_acao('AGENDAMENTO_CRIADO', $id_cliente, "Agendamento #{$id} para {$data_hora}");

// Eventos de segurança (logs/security.log):
log_seguranca('TENTATIVA_LOGIN_FALHA', "Email: {$email}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? ''));
```

---

## APIs JSON Internas

| Endpoint | Parâmetros | Retorno |
|----------|-----------|---------|
| `backend/controllers/medicos_por_especialidade.php` | `GET ?id_especialidade=N` | `{"medicos": [...]}` |
| `backend/controllers/horarios_disponiveis.php` | `GET ?id_medico=N&data=YYYY-MM-DD` | `{"horarios": ["09:00", ...]}` |

Ambos requerem `is_autenticado()` e retornam HTTP 401 se não autenticado.

---

## Adicionar Nova Feature (passo a passo)

1. **Controller** — criar `backend/controllers/nova_feature_controller.php`
   - `require_once BASE_PATH . '/backend/config/config.php';`
   - `require_once BASE_PATH . '/backend/utils/seguranca.php';`
   - `require_login();` ou `require_admin();`
   - Validar CSRF para POST
   - Lógica com prepared statements
   - `log_acao()` + `set_flash_message()` + `redirect()`

2. **View** — criar ou adicionar ação em `backend/views/painel_X.php`
   - Incluir variáveis CSS do design system
   - Usar `get_flash_message()` no topo
   - HTML com `htmlspecialchars()` em todo output de dados do banco

3. **Migration** (se precisar de nova tabela/coluna) — criar `sql/migrate_nome.sql` e adicionar verificação em `Conexao::aplicarMigracoes()`

4. **Link na navbar/sidebar** — editar `backend/includes/header.php` ou o menu do painel correspondente

---

## Migrações de Banco

Para adicionar tabela ou coluna sem resetar dados:

```php
// Em backend/config/conexao.php → método aplicarMigracoes()
private function aplicarMigracoes() {
    // Padrão existente: verifica existência da tabela antes de migrar
    $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='nova_tabela'");
    if (!$stmt->fetchColumn()) {
        $sql = file_get_contents(BASE_PATH . '/sql/migrate_nova_tabela.sql');
        $this->pdo->exec($sql);
    }
}
```

---

## Credenciais de Teste

| Usuário | Email | Senha | Tipo |
|---------|-------|-------|------|
| Admin | admin@clinica.com | admin123 | admin |
| Cliente | joao@example.com | admin123 | cliente |

**Médicos cadastrados:** Dra. Ana Souza (Cardiologia), Dr. Carlos Lima (Dermatologia), Dra. Beatriz Rocha (Oftalmologia).

---

## URLs & Endpoints Públicos

| URL | Descrição | Autenticação |
|-----|-----------|-------------|
| `/` ou `/index.php` | Homepage | Pública |
| `/especialidades.php` | Lista especialidades | Pública |
| `/medicos.php` | Lista médicos | Pública |
| `/exames.php` | Lista exames | Pública |
| `/cadastro/login.php` | Login | Pública |
| `/cadastro/criar_conta.php` | Cadastro | Pública |
| `/backend/views/painel_cliente.php` | Dashboard cliente | `require_cliente` |
| `/backend/views/painel_admin.php` | Dashboard admin | `require_admin` |
| `/backend/views/painel_medico.php` | Dashboard médico | `require_medico` |
| `/backend/views/painel_recepcionista.php` | Dashboard recepcionista | `require_recepcionista` |

---

## Skills Disponíveis

| Skill | Uso |
|-------|-----|
| `/db-reset` | Apaga e recria o banco SQLite do zero |
| `/db-seed` | Popula o banco com dados de teste |
| `/db-status` | Exibe contagem de registros por tabela |
| `/php-lint` | Verifica sintaxe PHP em todos os arquivos |
| `/php-check` | Detecta padrões inseguros no código |
| `/routes` | Lista todos os endpoints e ações do projeto |
