# Spec: Painéis de Usuário

**Módulo:** `backend/views/painel_*.php`  
**Status geral:** ✅ Implementado (4 painéis: cliente, admin, médico, recepcionista)

---

## Padrão Comum a Todos os Painéis

### Layout
- Navbar fixa no topo (herdada de `header.php`)
- Sidebar esquerda com menu de navegação (links com `?acao=`)
- Conteúdo principal à direita
- Flash message no topo do conteúdo (via `get_flash_message()`)

### Segurança
- Cada painel tem seu guard no topo: `require_admin()`, `require_medico()`, etc.
- Sub-views do admin checam `defined('PAINEL_ADMIN_LOADED')` para impedir acesso direto via URL
- Todos os outputs de dados do banco usam `htmlspecialchars()`

### Roteamento
```php
$acao = sanitizar_input($_GET['acao'] ?? 'dashboard');
switch ($acao) {
    case 'dashboard': /* ... */ break;
    case 'agendamentos': /* ... */ break;
    // ...
}
```

---

## RF-PAINEL-01: Painel do Cliente

**View:** `backend/views/painel_cliente.php`  
**Guard:** `require_cliente()`

### Ações disponíveis

| `?acao=` | Conteúdo | Status |
|----------|----------|--------|
| `dashboard` | Próximas consultas/exames (resumo) | ✅ |
| `agendamentos` | Histórico completo com status e valores | ✅ |
| `agendar` | Formulário para agendar consulta | ✅ |
| `exames` | Formulário para solicitar exame | ✅ |
| `perfil` | Dados pessoais (read-only) | ✅ |

### Dashboard (ação padrão)

- Próximas 5 consultas com status `pendente` ou `confirmado`
- Próximos exames com status `pendente` ou `confirmado`
- Cards de atalho para "Agendar Consulta" e "Solicitar Exame"
- Mensagem de boas-vindas com nome do usuário

### Lacunas / Melhorias

- ❌ **Sem edição de perfil**: cliente só visualiza dados, não consegue alterar nome, telefone ou email
- ❌ **Sem troca de senha**: cliente não tem formulário de alteração de senha
- ⚠️ **Sem paginação em agendamentos**: todos os agendamentos listados de uma vez
- ❌ **Sem histórico de exames separado**: consultas e exames misturados na mesma lista
- ❌ **Sem comprovante/PDF**: cliente não consegue baixar comprovante de agendamento

---

## RF-PAINEL-02: Painel Administrativo

**View:** `backend/views/painel_admin.php`  
**Guard:** `require_admin()`  
**Sub-views incluídas dinamicamente:** `especialidade_listar.php`, `especialidade_form.php`, `exame_listar.php`, `exame_form.php`, `medico_listar.php`, `medico_form.php`, `horarios_form.php`, `financeiro.php`, `relatorios.php`

### Ações disponíveis

| `?acao=` | Conteúdo | Status |
|----------|----------|--------|
| `dashboard` | KPIs: total clientes, agendamentos por status, especialidades | ✅ |
| `clientes` | Listagem paginada de todos os clientes | ✅ |
| `agendamentos` | Todos os agendamentos com filtros avançados | ✅ |
| `especialidades` | Lista de especialidades | ✅ |
| `especialidade_form` | Criar/editar especialidade | ✅ |
| `exames` | Lista de exames | ✅ |
| `exame_form` | Criar/editar exame | ✅ |
| `medicos` | Lista de médicos | ✅ |
| `medico_form` | Criar/editar médico | ✅ |
| `horarios` | Gerenciar horários do médico | ✅ |
| `financeiro` | Gestão financeira e repasses | ✅ |
| `relatorios` | Relatórios analíticos | ⚠️ Parcial |

### Dashboard Admin

**KPIs exibidos:**
- Total de clientes cadastrados
- Agendamentos pendentes (com badge de aviso)
- Agendamentos confirmados
- Total de especialidades ativas
- Agendamentos concluídos no mês

**Acesso rápido:** Menu em grid com todos os módulos e ícones Font Awesome.

### Clientes (ação `clientes`)

- Listagem com paginação (10 por página)
- Exibe: nome, email, CPF formatado, telefone, data de cadastro, status ativo/inativo
- Busca por nome ou email

### Lacunas / Melhorias

- ❌ **Sem CRUD de recepcionistas na UI**: não há forma de criar/editar recepcionistas pelo painel
- ❌ **Sem ativar/inativar clientes pela UI**: campo `ativo` existe no banco mas não há toggle no admin
- ❌ **Sem criação de novo admin pela UI**: apenas o admin inicial (seed) existe
- ⚠️ **Filtro de agendamentos**: filtros existem mas podem não persistir entre paginações
- ❌ **Sem log de auditoria visível**: logs existem em arquivo mas não há viewer no admin
- ❌ **Sem exportação de dados**: não há botão de export CSV/Excel em nenhuma listagem

---

## RF-PAINEL-03: Painel do Médico

**View:** `backend/views/painel_medico.php`  
**Guard:** `require_medico()`

### Ações disponíveis

| `?acao=` | Conteúdo | Status |
|----------|----------|--------|
| `dashboard` | KPIs pessoais + consultas de hoje | ✅ |
| `agenda` | Próximas consultas confirmadas | ✅ |
| `historico` | Consultas realizadas (concluídas) | ✅ |
| `perfil` | Dados cadastrais + troca de senha | ✅ |

### Dashboard Médico

**KPIs exibidos:**
- Consultas de hoje (status `pendente` ou `confirmado` com data = hoje)
- Consultas nos próximos 7 dias
- Total de consultas concluídas (histórico)
- Próxima consulta agendada (destaque)

### Agenda

- Lista de próximas consultas com status `pendente` ou `confirmado`
- Ordenadas por `data_hora ASC`
- Exibe: paciente (nome), data/hora formatada, especialidade, status

### Histórico

- Consultas com status `concluído` do médico logado
- Exibe: paciente, data, especialidade, valor recebido (`valor_medico`)

### Perfil (alterar senha)

- Exibe dados read-only: nome, CRM, especialidade, email, telefone, valor consulta
- Formulário de troca de senha:
  - Senha atual (validada)
  - Nova senha (mín 6 chars)
  - Confirmação da nova senha
  - Atualiza tanto em `medicos.senha_hash` quanto em `clientes.senha_hash`

### Lacunas / Melhorias

- ❌ **Médico não vê valor recebido no dashboard**: `valor_medico` aparece só no histórico, não em KPIs financeiros
- ❌ **Sem acesso ao prontuário**: médico não vê histórico clínico do paciente
- ❌ **Sem confirmação de consultas**: médico não pode confirmar ou recusar — isso é papel da recepcionista
- ⚠️ **Histórico sem filtro de período**: todas as consultas concluídas listadas de uma vez

---

## RF-PAINEL-04: Painel da Recepcionista

**View:** `backend/views/painel_recepcionista.php`  
**Guard:** `require_recepcionista()`

### Ações disponíveis

| `?acao=` | Conteúdo | Status |
|----------|----------|--------|
| `dashboard` | KPIs operacionais do dia | ✅ |
| `agendamentos` | Lista com ações de confirmar/cancelar/concluir | ✅ |
| `novo_agendamento` | Criar agendamento em nome de um paciente | ⚠️ Parcial |
| `clientes` | Buscar e visualizar clientes | ✅ |
| `medicos` | Visualizar médicos ativos | ✅ |

### Dashboard Recepcionista

**KPIs exibidos:**
- Agendamentos hoje (total)
- Pendentes de confirmação
- Confirmados para hoje
- Total de clientes no sistema

### Fluxo de trabalho (workflow diário)

```
Manhã → Ver lista de agendamentos do dia
     → Confirmar pendentes após verificação
     ↓
Durante o dia → Marcar como concluído após atendimento
     ↓
Eventual → Cancelar se paciente não compareceu ou cancelou via telefone
```

### Lacunas / Melhorias

- ⚠️ **`novo_agendamento`**: fluxo de criar agendamento pela recepcionista existe no controller mas precisa de verificação da UI completa
- ❌ **Sem filtro por médico ou especialidade**: recepcionista vê todos os agendamentos misturados
- ❌ **Sem agendamento por telefone com busca de paciente**: recepcionista não tem busca integrada para encontrar o paciente antes de criar o agendamento
- ❌ **Sem visão de agenda do dia em formato calendário/timeline**: apenas lista tabular
- ❌ **Sem impressão de lista de pacientes do dia**: não há geração de lista de chamada

---

## Critérios de Aceitação dos Painéis

### Cliente
- [ ] Acesso a `?acao=agendar` com a lista de especialidades populada da DB
- [ ] Mudança de especialidade atualiza dinamicamente a lista de médicos via AJAX
- [ ] Mudança de médico + data atualiza dinamicamente os horários disponíveis
- [ ] Flash message de sucesso após agendamento criado

### Admin
- [ ] Sub-views não podem ser acessadas diretamente via URL (requerem `PAINEL_ADMIN_LOADED`)
- [ ] Paginação de clientes funciona com parâmetro `?pagina=N`
- [ ] Dashboard exibe contagens reais do banco (não valores mockados)

### Médico
- [ ] Médico só vê seus próprios agendamentos (filtro por `id_medico` da sessão)
- [ ] Troca de senha valida a senha atual antes de atualizar
- [ ] Troca de senha sincroniza hash em `medicos` e em `clientes`

### Recepcionista
- [ ] Confirmar agendamento `pendente` → muda status para `confirmado`
- [ ] Cancelar agendamento `confirmado` → muda status para `cancelado`
- [ ] Concluir agendamento `confirmado` → muda status para `concluído`
- [ ] Recepcionista não consegue acessar painel admin via URL direta
