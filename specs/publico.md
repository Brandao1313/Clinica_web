# Spec: Páginas Públicas & Navegação

**Módulo:** Arquivos na raiz + `cadastro/`  
**Status geral:** ✅ Implementado

---

## Visão Geral

As páginas públicas são acessíveis sem autenticação e servem como vitrine da clínica. O conteúdo é majoritariamente dinâmico, buscado do banco de dados.

---

## RF-PUB-01: Homepage

**Arquivo:** `index.php`

### Seções

| Seção | Conteúdo | Fonte |
|-------|----------|-------|
| Hero/Banner | Imagem de fundo + CTA "Agendar Consulta" / "Saiba Mais" | Estático (`assets/imagens/`) |
| Especialidades destaque | Grid com até 6 especialidades ativas | Banco — tabela `especialidades` |
| Sobre resumida | Texto institucional + CTA "Conhecer Clínica" | Estático |
| Médicos destaque | Grid com até 4 médicos ativos | Banco — tabela `medicos` |
| CTA Final | "Agende sua Consulta Hoje" com botão para login/cadastro | Condicional ao status de auth |

### Comportamento condicional por autenticação

| Estado | CTA exibido |
|--------|-------------|
| Não autenticado | "Agende Agora" → `cadastro/login.php` |
| Cliente logado | "Agendar Consulta" → `painel_cliente.php?acao=agendar` |
| Admin/médico/recepcionista | Link para o painel correspondente |

### Lacunas / Melhorias

- ⚠️ **Imagens do hero são estáticas**: sem painel de admin para editar banners
- ❌ **Sem carrossel/depoimentos**: sem testemunhos de pacientes
- ❌ **Sem bloco de contato**: endereço, telefone, WhatsApp não visíveis na home

---

## RF-PUB-02: Especialidades

**Arquivo:** `especialidades.php`

### Conteúdo

- Grid de todas as especialidades com `ativo = 1`
- Cada card exibe:
  - Ícone (via `obter_icone_especialidade($nome)`)
  - Nome
  - Descrição
  - Tempo estimado de consulta (via `obter_tempo_estimado()`, hardcoded)
  - Selo "Mais agendado" quando aplicável (via `eh_popular()`, hardcoded/fictício)
  - Botão "Agendar Consulta" → login se não autenticado, `painel_cliente.php?acao=agendar` se autenticado

### Lacunas / Melhorias

- ⚠️ **Tempo estimado e popularidade são hardcoded**: `obter_tempo_estimado()` e `eh_popular()` retornam valores fixos, não calculados do banco
- ❌ **Sem contagem real de agendamentos por especialidade**: "Mais agendado" não reflete dados reais
- ❌ **Sem página de detalhe por especialidade**: sem `/especialidade/cardiologia` com médicos da área

---

## RF-PUB-03: Médicos

**Arquivo:** `medicos.php`

### Conteúdo

- Grid de todos os médicos com `ativo = 1`
- Cada card exibe:
  - Foto do médico (placeholder se `foto` for NULL/vazio)
  - Nome
  - Especialidade (JOIN com `especialidades`)
  - CRM
  - Valor da consulta (`formatar_valor()`)
  - Bio (truncada com `truncar_texto()`)
  - Horários de atendimento resumidos (dias da semana)
  - Botão "Ver Perfil" → `medico_detalhe.php?id=N`
  - Botão "Agendar" → login ou painel

### Placeholder de foto

```php
// Se $medico['foto'] for vazio ou null:
<div class="medico-foto-placeholder">
    <i class="fa-solid fa-user-doctor"></i>
</div>
```

### Lacunas / Melhorias

- ⚠️ **Sem busca/filtro por especialidade na página de médicos**: cliente precisa rolar toda a lista
- ⚠️ **Sem upload de foto via admin**: admin insere URL manualmente no campo foto
- ❌ **Sem ordenação configurável**: médicos exibidos em ordem de cadastro (ID)

---

## RF-PUB-04: Detalhe do Médico

**Arquivo:** `medico_detalhe.php`

### Conteúdo

- Dados completos do médico
- Bio completa
- Horários de atendimento detalhados por dia
- Valor da consulta
- Botão "Agendar com este médico" → pre-seleciona médico no formulário de agendamento

### Lacunas / Melhorias

- ❌ **Pré-seleção de médico no formulário de agendamento**: o botão redireciona mas o formulário não pre-seleciona o médico automaticamente

---

## RF-PUB-05: Exames

**Arquivo:** `exames.php`

### Conteúdo

- Grid de todos os exames com `ativo = 1`
- Cada card exibe:
  - Ícone (via `obter_icone_exame($nome)`)
  - Nome
  - Descrição
  - Preço (`formatar_valor()`)
  - Tempo estimado (hardcoded)
  - Botão "Solicitar Exame" → login ou painel

---

## RF-PUB-06: Sobre

**Arquivo:** `sobre.php`

### Conteúdo

- Texto estático sobre a clínica
- Seção de valores institucionais (`valor-card`)
- Estatísticas (anos de experiência, médicos, pacientes atendidos — valores fictícios)

### Lacunas / Melhorias

- ❌ **Conteúdo 100% estático**: não há painel para editar textos institucionais
- ❌ **Mapa/localização**: sem endereço, mapa ou informações de contato

---

## RF-PUB-07: Login

**Arquivo:** `cadastro/login.php`

### Conteúdo

- Formulário com campos: email + senha
- Links: "Esqueci minha senha" e "Criar conta"
- Flash messages de erro/sucesso
- CSRF token hidden input

### Lacunas / Melhorias

- ❌ **Sem "mostrar senha" (toggle)**: campo senha não tem botão de visibilidade
- ❌ **Sem login social**: sem "entrar com Google/Facebook"

---

## RF-PUB-08: Cadastro

**Arquivo:** `cadastro/criar_conta.php`

### Campos do formulário

- Nome completo
- Email
- CPF (sem máscara — 11 dígitos)
- Telefone (sem máscara — 10/11 dígitos)
- Data de nascimento (`type="date"`)
- Senha
- Confirmação de senha

### Lacunas / Melhorias

- ⚠️ **Sem máscaras de input**: CPF e telefone são campos de texto puro — usuário pode errar o formato
- ⚠️ **Sem validação em tempo real**: erros aparecem só após submit
- ❌ **Sem confirmação de email**: conta ativa imediatamente após cadastro

---

## RF-PUB-09: Recuperação de Senha

**Arquivo:** `cadastro/esqueci_senha.php`

### Etapa 1: Verificar identidade

- Campos: telefone + CPF
- Se dados corretos → mostra formulário de nova senha (mesma página)

### Etapa 2: Nova senha

- Campos: nova senha + confirmação
- Atualiza `senha_hash` no banco

### Lacunas / Melhorias

- ⚠️ **Sem envio de email**: recovery por telefone+CPF é mecanismo incomum — usuário espera receber email
- ❌ **Sem indicação visual clara das 2 etapas**: UX pode confundir usuário

---

## Navegação (header.php)

**Arquivo:** `backend/includes/header.php`

### Menu público (não autenticado)

```
Home | Especialidades | Médicos | Exames | Sobre | [Entrar] [Cadastrar]
```

### Menu autenticado (cliente)

```
Home | Especialidades | Médicos | Exames | Sobre | [Painel] [Sair]
```

### Menu autenticado (admin)

```
Home | Especialidades | Médicos | Exames | [Painel Admin] [Sair]
```

### Menu autenticado (médico)

```
Home | [Meu Painel] [Sair]
```

### Lacunas / Melhorias

- ⚠️ **Navbar sem busca global**: não há campo de busca de médico ou especialidade no header
- ❌ **Sem indicador de notificações**: médico não vê badge de "2 novas consultas hoje" no header

---

## Critérios de Aceitação das Páginas Públicas

- [ ] Homepage exibe especialidades reais do banco (não mockadas)
- [ ] Médico inativo não aparece em nenhuma página pública
- [ ] Especialidade inativa não aparece em nenhuma página pública
- [ ] Exame inativo não aparece na listagem
- [ ] Usuário não autenticado que clica "Agendar" é redirecionado para login, e após login é redirecionado para o formulário de agendamento
- [ ] `htmlspecialchars()` em todo conteúdo dinâmico (nome de médico, bio, descrição de especialidade)
- [ ] Página de médico inexistente ou inativo exibe 404 ou redireciona
