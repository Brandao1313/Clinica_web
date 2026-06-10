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

- **PHP 7.4+** (recomendado 8.0+)
- **MySQL 5.7+** ou **MariaDB**
- **Apache** ou outro servidor web (com suporte a rewrite URLs)
- **XAMPP**, **WAMP** ou instalação local de PHP + Apache + MySQL

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
- Iniciar **MySQL** (botão Start)

#### No WAMP:
- Clicar no ícone WAMP na bandeja do sistema
- Garantir que está "verde" (todos os serviços rodando)

### 3. Acessar a Aplicação

```
http://localhost/Cinica_web
```

---

## 🗄️ Configuração do Banco de Dados

### Passo 1: Acessar phpMyAdmin

```
http://localhost/phpmyadmin
```

### Passo 2: Criar Banco de Dados

1. Clique em "Novo" ou "New Database"
2. Digite: `clinica_db`
3. Clique em "Criar" ou "Create"

### Passo 3: Importar Tabelas

1. Selecione o banco `clinica_db`
2. Vá para a aba **SQL**
3. Abra o arquivo `Cinica_web/sql/criar_tabelas.sql`
4. Copie todo o conteúdo
5. Cole no editor SQL do phpMyAdmin
6. Clique em **Executar**

Ou, alternativa via terminal:

```bash
mysql -u root clinica_db < sql/criar_tabelas.sql
```

### Verificar Tabelas

Após importar, você deve ver as tabelas:
- `clientes`
- `especialidades`
- `exames`
- `agendamentos`
- `redefinicao_senha`

---

## ⚙️ Configuração da Aplicação

### Arquivo: `backend/config/config.php`

Verifique se as configurações estão corretas:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');              // Usuário MySQL (padrão: root)
define('DB_PASS', '');                  // Senha MySQL (padrão: vazio)
define('DB_NAME', 'clinica_db');
define('DB_PORT', 3306);
```

#### Se você tem senha no MySQL:

```php
define('DB_PASS', 'sua_senha_aqui');
```

---

## 📱 Como Usar

### 1️⃣ Acessar a Página Inicial

```
http://localhost/Cinica_web/index.html
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
├── index.html                          # Página inicial
├── sobre.html                          # Página sobre
├── especialidades.php                  # Especialidades dinâmicas
├── exames.php                          # Exames dinâmicos
├── estilo.css                          # Estilos globais
│
├── imagens/                            # Logo e imagens
│
├── cadastro/
│   ├── login.php                       # Página de login
│   ├── criar_conta.php                 # Página de cadastro
│   └── esqueci_senha.php               # Página de redefinição
│
├── backend/
│   ├── config/
│   │   ├── config.php                  # Configurações globais
│   │   └── conexao.php                 # Classe de conexão BD
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
├── sql/
│   └── criar_tabelas.sql               # Script de criação do BD
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

### ❌ Erro: "Fatal error: Uncaught mysqli_sql_exception"

**Solução**: Verificar conexão com BD
- Verificar se MySQL está rodando
- Verificar se banco `clinica_db` existe
- Verificar credenciais em `backend/config/config.php`

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
