# 🏥 Clínica Saúde & Bem-Estar - Aplicação Dinâmica

Aplicação web para gerenciamento de clínica médica com autenticação de usuários, agendamentos de consultas/exames, painel de cliente e painel administrativo.

## 📋 Índice

1. [Requisitos](#requisitos)
2. [Instalação](#instalação)
3. [Configuração do Banco de Dados](#configuração-do-banco-de-dados)
4. [Configuração da Aplicação](#configuração-da-aplicação)
5. [Como Usar](#como-usar)
6. [Estrutura do Projeto](#estrutura-do-projeto)
7. [Credenciais de Teste](#credenciais-de-teste)
8. [Segurança](#segurança)
9. [Troubleshooting](#troubleshooting)

---

## 📦 Requisitos

- **PHP 8.0+** com extensão **pdo_sqlite** (vem habilitada por padrão)
- **Apache** ou outro servidor web (com suporte a rewrite URLs)
- **XAMPP**, **WAMP** ou instalação local de PHP + Apache

> O banco de dados é **SQLite** (arquivo único, sem necessidade de servidor de banco de dados separado).

---

## 🚀 Instalação

### 1. Baixar e Configurar o Projeto

```bash
# Clonar ou descompactar o projeto na pasta htdocs do XAMPP
# Exemplo: C:\xampp\htdocs\Cinica_web
cd C:\xampp\htdocs\Cinica_web
```

### 2. Iniciar Serviços

#### No XAMPP:
- Abrir **XAMPP Control Panel**
- Iniciar **Apache** (botão Start)

> Não é necessário iniciar o MySQL — o banco de dados SQLite é criado automaticamente.

#### No WAMP:
- Clicar no ícone WAMP na bandeja do sistema
- Garantir que o **Apache** está "verde"

### 3. Acessar a Aplicação

```
http://localhost/Cinica_web
```

---

## 🗄️ Configuração do Banco de Dados

O banco de dados é um arquivo **SQLite** localizado em `database/clinica.sqlite`.

Na primeira requisição à aplicação, a classe `Conexao` (`backend/config/conexao.php`)
verifica se esse arquivo existe e, caso não exista, **cria automaticamente** o banco
executando o script `sql/schema.sqlite.sql`, que contém a estrutura das tabelas e os
dados iniciais (especialidades, exames e usuários de teste).

Não é necessário nenhum passo manual de criação/importação do banco.

### Tabelas criadas automaticamente
- `clientes`
- `especialidades`
- `exames`
- `agendamentos`
- `redefinicao_senha`

### Recriar o banco do zero

Para recomeçar com dados limpos, basta apagar o arquivo `database/clinica.sqlite`
(ele será recriado automaticamente na próxima requisição).

---

## ⚙️ Configuração da Aplicação

### Arquivo: `backend/config/config.php`

A configuração principal do banco é a constante `DB_PATH`, que aponta para o arquivo SQLite:

```php
define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/../../database/clinica.sqlite');
```

Pode ser sobrescrita definindo a variável de ambiente `DB_PATH` (útil em Docker).

A `SITE_URL` é detectada automaticamente a partir do caminho do projeto e do host
da requisição, podendo também ser sobrescrita via variável de ambiente `SITE_URL`.

---

## 📱 Como Usar

### 1️⃣ Acessar a Página Inicial

```
http://localhost/Cinica_web/index.php
```

### 2️⃣ Criar uma Conta

1. Clique em **"Login"** no menu superior
2. Clique em **"Criar conta"**
3. Preencha os dados:
   - **Nome completo**: Seu nome
   - **Email**: email@example.com
   - **CPF**: 12345678901 (válido apenas para teste)
   - **Telefone**: (opcional)
   - **Data de nascimento**: (opcional, mas deve ter 18+ anos)
   - **Senha**: min. 6 caracteres
4. Clique em **"Criar conta"**

### 3️⃣ Fazer Login

1. Digite seu **Email** e **Senha**
2. Clique em **"Entrar"**
3. Você será redirecionado para o **Painel do Cliente**

### 4️⃣ Usar o Painel do Cliente

No painel, você pode:

- 📊 **Dashboard**: Ver resumo e próximos agendamentos
- 👤 **Meu Perfil**: Visualizar dados pessoais
- 📅 **Meus Agendamentos**: Listar e cancelar agendamentos
- ➕ **Agendar Consulta**: Escolher especialidade e data/hora
- 🧬 **Solicitar Exame**: Escolher exame e data/hora

### 5️⃣ Acessar Painel Admin

Como **admin** (usar credenciais de teste abaixo), você pode:

1. Ir para **Painel Admin** no menu lateral
2. Ver **dashboard** com estatísticas
3. **Listar clientes** com paginação
4. **Ver agendamentos** de todos os clientes
5. **Gerenciar especialidades** e **exames**

---

## 📁 Estrutura do Projeto

```
Cinica_web/
│
├── index.php                           # Página inicial
├── sobre.php                           # Página sobre
├── especialidades.php                  # Especialidades dinâmicas
├── exames.php                          # Exames dinâmicos
├── estilo.css                          # Estilos globais
│
├── imagens/                            # Logo e imagens
│
├── includes/
│   ├── header.php                      # Cabeçalho/navbar compartilhados
│   └── footer.php                      # Rodapé compartilhado
│
├── cadastro/
│   ├── login.php                       # Página de login
│   ├── criar_conta.php                 # Página de cadastro
│   └── esqueci_senha.php               # Página de redefinição
│
├── backend/
│   ├── config/
│   │   ├── config.php                  # Configurações globais
│   │   └── conexao.php                 # Classe de conexão BD (PDO/SQLite)
│   │
│   ├── auth/
│   │   ├── registrar.php               # Processa cadastro
│   │   ├── logar.php                   # Processa login
│   │   ├── deslogar.php                # Processa logout
│   │   └── redefinir_senha.php         # Processa reset de senha
│   │
│   ├── controllers/
│   │   └── agendamento_controller.php  # Processa agendamentos
│   │
│   ├── utils/
│   │   ├── validacao.php               # Funções de validação
│   │   ├── seguranca.php               # Funções de segurança
│   │   └── funcoes_gerais.php          # Funções auxiliares
│   │
│   └── views/
│       ├── painel_cliente.php          # Painel do cliente
│       └── painel_admin.php            # Painel administrativo
│
├── database/
│   └── clinica.sqlite                  # Banco de dados SQLite (criado automaticamente)
│
├── sql/
│   ├── schema.sqlite.sql               # Script de criação do BD (SQLite, usado pela aplicação)
│   └── criar_tabelas.sql               # Script MySQL antigo (referência histórica)
│
└── README.md                           # Este arquivo
```

---

## 🔐 Credenciais de Teste

O banco de dados vem com dados iniciais:

### Admin (para testar painel admin)
- **Email**: `admin@clinica.com`
- **Senha**: `admin123`

### Cliente Teste
- **Email**: `joao@example.com`
- **Senha**: `admin123`

⚠️ **IMPORTANTE**: Trocar essas senhas em produção!

---

## 🛡️ Segurança

A aplicação implementa várias medidas de segurança:

### 1. **Autenticação & Autorização**
- ✅ Senhas com bcrypt (password_hash/password_verify)
- ✅ Sessões PHP seguras com regeneração periódica de session ID
- ✅ Proteção de páginas com require_login() e require_admin()

### 2. **Proteção contra SQL Injection**
- ✅ Prepared statements com bindParam
- ✅ Nunca usar concatenação de strings em queries

### 3. **Proteção contra XSS**
- ✅ htmlspecialchars() em toda saída HTML
- ✅ Sanitização de inputs com trim() e sanitizar_input()

### 4. **Proteção contra CSRF**
- ✅ Geração de tokens CSRF (implementável em formulários)
- ✅ Validação de referrer quando necessário

### 5. **Validação de Dados**
- ✅ Validação de CPF (dígito verificador)
- ✅ Validação de email
- ✅ Validação de força de senha
- ✅ Validação no backend (não confiar apenas no frontend)

### 6. **Logs de Atividade**
- ✅ Registro de login/logout/ações importantes
- ✅ Arquivo de log: `logs/atividades.log`

---

## 🔧 Troubleshooting

### ❌ Erro relacionado a PDO/SQLite

**Solução**: Verificar a extensão do PHP
- Confirmar que `pdo_sqlite` está habilitada (`php -m | grep sqlite`)
- Verificar se a pasta `database/` tem permissão de escrita
- Apagar `database/clinica.sqlite` para forçar recriação do banco

### ❌ "Página em branco"

**Solução**: Verificar logs PHP
- No XAMPP: `apache/logs/error.log`
- Verificar se PHP está processando `.php`

### ❌ "401 Unauthorized" ou "Method Not Allowed"

**Solução**: Verificar arquivo `.htaccess`
- Garantir que Apache tem módulo `mod_rewrite` ativo
- Se não tiver, remover `.htaccess` ou configurar manualmente

### ❌ Email não está sendo enviado

**Solução**: Email é simulado!
- Verificar arquivo `logs/emails.log` para ver "envios"
- Para envio real, implementar SwiftMailer ou PHPMailer

### ❌ CPF inválido durante cadastro

**Solução**: Usar CPF válido
- CPF de teste inválido: `00000000000`
- Gerar CPF válido ou usar: `12345678901` (para teste apenas)
- Validação inclui verificação de dígito verificador

---

## 📊 Estatísticas do Código

- **Arquivos PHP**: 15+
- **Linhas de código**: ~3000+
- **Tabelas BD**: 5
- **Funcionalidades**: 20+
- **Páginas**: 10+

---

## 🎯 Próximos Passos (Melhorias)

Para melhorar a aplicação em futuras versões:

- [ ] Envio real de emails (SwiftMailer/PHPMailer)
- [ ] Dashboard com gráficos (Chart.js)
- [ ] API REST para mobile app
- [ ] Agendamento automático de consultas
- [ ] Relatórios em PDF
- [ ] Sistema de pagamento
- [ ] Notificações por SMS
- [ ] Integração com Google Calendar
- [ ] Sistema de avaliação de consultas
- [ ] Backup automático do BD

---

## 📞 Suporte

Para erros ou dúvidas:

1. Verificar seção **Troubleshooting** acima
2. Verificar logs em `logs/atividades.log`
3. Verificar console do navegador (F12 > Console)
4. Verificar arquivos de configuração

---

## 📄 Licença

Projeto educacional - Livre para uso e modificação.

---

**Desenvolvido com ❤️ para Clínica Saúde & Bem-Estar**
