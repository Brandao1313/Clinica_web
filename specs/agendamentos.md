# Spec: Agendamentos

**Módulo:** `backend/controllers/agendamento_controller.php` + `backend/controllers/horarios_disponiveis.php`  
**Status geral:** ✅ Implementado (consultas e exames)

---

## Visão Geral

O agendamento é a funcionalidade central do sistema. Há dois tipos: **consulta médica** (vinculada a especialidade + médico + horário da grade) e **exame laboratorial/imagem** (vinculado apenas ao exame e data/hora livre).

---

## RF-AGEND-01: Agendar Consulta Médica

**Ator:** Cliente  
**Arquivo:** `agendamento_controller.php` (ação `agendar_consulta`)  
**View:** `painel_cliente.php?acao=agendar`

### Fluxo

```
1. Cliente seleciona especialidade
   → JS dispara AJAX para medicos_por_especialidade.php
   → Select de médico é populado dinamicamente

2. Cliente seleciona médico + data
   → JS dispara AJAX para horarios_disponiveis.php
   → Select de horário é populado com slots livres

3. Cliente confirma → POST para agendamento_controller.php
   → Validações server-side
   → INSERT em agendamentos com status='pendente'
   → Redirect com flash message
```

### Validações (server-side obrigatórias)

| Validação | Regra |
|-----------|-------|
| Especialidade | `id_especialidade > 0`, existe no banco, ativa |
| Médico | `id_medico > 0`, ativo, pertence à especialidade escolhida |
| Data | Formato `YYYY-MM-DD`, válida |
| Horário | Formato `HH:MM`, dentro da grade do médico no dia da semana |
| Data/hora futura | Mínimo 1 hora no futuro (`validar_data_agendamento()`) |
| Disponibilidade | Médico não tem outro agendamento `pendente` ou `confirmado` naquele `data_hora` |
| CSRF | Token válido obrigatório |

### Regras de negócio

- `valor_total` = `medico.valor_consulta`
- `valor_medico` = `valor_total × medico.percentual_medico / 100` (padrão 70%)
- Status inicial: `'pendente'`
- `status_pagamento` inicial: `'pendente'`
- Um cliente pode ter múltiplos agendamentos pendentes simultâneos
- Um médico **não** pode ter dois agendamentos com status `pendente` ou `confirmado` no mesmo `data_hora`

### API: médicos por especialidade

**Endpoint:** `GET /backend/controllers/medicos_por_especialidade.php?id_especialidade=N`  
**Autenticação:** `is_autenticado()` — retorna HTTP 401 se não autenticado

**Resposta:**
```json
{
  "medicos": [
    { "id": 1, "nome": "Dra. Ana Souza", "valor_consulta": 250.00, "valor_formatado": "R$ 250,00" }
  ]
}
```

### API: horários disponíveis

**Endpoint:** `GET /backend/controllers/horarios_disponiveis.php?id_medico=N&data=YYYY-MM-DD`  
**Autenticação:** `is_autenticado()` — retorna HTTP 401 se não autenticado

**Lógica:**
1. Busca grade `horarios_atendimento` do médico no dia da semana correspondente
2. Gera todos os slots no intervalo (`gerar_slots_horario()`)
3. Remove slots com agendamento `pendente` ou `confirmado` no banco
4. Se `data = hoje`: remove slots no passado

**Resposta:**
```json
{ "horarios": ["09:00", "09:30", "10:00"] }
```

### Lacunas / Melhorias

- ⚠️ **Sem validação de conflito no lado do cliente**: se usuário selecionar médico, depois mudar especialidade e não reselecionar médico, o select fica com valor antigo
- ⚠️ **Exames não têm grade de horários**: cliente digita data/hora livre sem verificar disponibilidade real
- ⚠️ **Sem confirmação por email/SMS**: cliente não recebe notificação de confirmação
- ❌ **Sem reagendamento**: só existe cancelar + criar novo — não há "alterar" agendamento
- ❌ **Sem lista de espera**: horário ocupado simplesmente retorna erro
- ❌ **Médico pode ter sobreposição via exame**: se um médico for designado a um exame, isso não é verificado no slot de consulta

---

## RF-AGEND-02: Solicitar Exame

**Ator:** Cliente  
**Arquivo:** `agendamento_controller.php` (ação `agendar_exame`)  
**View:** `painel_cliente.php?acao=exames`

### Campos

| Campo | Validação |
|-------|-----------|
| Exame | `id_exame > 0`, existe e está ativo |
| Data/hora | `datetime-local` input, deve ser futura (mín 1h) |
| Notas | Opcional, sanitizado |

### Regras de negócio

- `valor_total` = `exame.preco` (buscado no banco)
- `valor_medico` = 0 (exames não geram repasse ao médico por padrão)
- `id_especialidade` e `id_medico` ficam NULL
- Sem verificação de grade horária — qualquer data/hora futura é aceita
- Status inicial: `'pendente'`

### Lacunas / Melhorias

- ⚠️ **Sem capacidade/limite de exames por horário**: dois clientes podem agendar o mesmo exame no mesmo momento
- ❌ **Sem médico responsável pelo exame**: campo `id_medico` fica NULL, impossibilitando rastrear quem realizou

---

## RF-AGEND-03: Cancelar Agendamento

**Ator:** Cliente (próprio agendamento) ou Admin/Recepcionista (qualquer agendamento)  
**Arquivo:** `agendamento_controller.php` (ação `cancelar_agendamento`)

### Regras de negócio

- Cliente só pode cancelar agendamentos do próprio `id_cliente`
- Apenas status `'pendente'` ou `'confirmado'` podem ser cancelados (`pode_cancelar_agendamento()`)
- Status `'concluído'` e `'cancelado'` não podem ser alterados pelo cliente
- Cancelamento não gera reembolso automático (módulo financeiro não está vinculado)
- Ação registrada em `logs/atividades.log`

### Lacunas / Melhorias

- ❌ **Sem prazo mínimo de cancelamento**: cliente pode cancelar 1 minuto antes do horário
- ❌ **Sem notificação ao médico**: médico não é avisado do cancelamento
- ❌ **Sem motivo de cancelamento**: campo `notas` não é atualizado com o motivo

---

## RF-AGEND-04: Visualizar Agendamentos (Cliente)

**View:** `painel_cliente.php?acao=agendamentos`

### Dados exibidos

| Campo | Formatação |
|-------|-----------|
| Tipo | "Consulta" ou "Exame" |
| Especialidade / Exame | Nome |
| Médico | Nome (quando aplicável) |
| Data/hora | `formatar_data_hora()` → DD/MM/YYYY HH:MM |
| Status | Badge colorido via `get_classe_status()` |
| Valor | `formatar_valor()` → R$ XXX,XX |
| Ação | Botão "Cancelar" (se `pode_cancelar_agendamento()`) |

### Lacunas / Melhorias

- ⚠️ **Sem paginação**: todos os agendamentos carregados de uma vez — pode ser lento para clientes com muitos agendamentos
- ⚠️ **Sem filtro**: não é possível filtrar por status, data ou tipo
- ❌ **Sem impressão / comprovante**: cliente não pode gerar PDF do agendamento

---

## RF-AGEND-05: Gerenciar Agendamentos (Admin)

**View:** `painel_admin.php?acao=agendamentos`

### Funcionalidades

- Listar todos os agendamentos do sistema com paginação (10 por página)
- Filtros: status, médico, status de pagamento, data início/fim
- Ações inline: ver detalhes

### Lacunas / Melhorias

- ⚠️ **Sem ação de confirmar**: admin vê agendamentos mas confirmação é papel da recepcionista
- ❌ **Sem exportação**: não é possível exportar lista para CSV/Excel
- ❌ **Sem bulk actions**: não é possível confirmar/cancelar múltiplos de uma vez

---

## RF-AGEND-06: Gerenciar Agendamentos (Recepcionista)

**Arquivo:** `backend/controllers/recepcionista_controller.php`  
**View:** `painel_recepcionista.php?acao=agendamentos`

### Ações disponíveis

| Ação | Transição de status | Condição |
|------|--------------------|----|
| `confirmar_agendamento` | `pendente` → `confirmado` | Status deve ser `pendente` |
| `cancelar_agendamento` | `pendente`/`confirmado` → `cancelado` | Status deve ser pendente ou confirmado |
| `concluir_agendamento` | `confirmado` → `concluído` | Status deve ser `confirmado` |
| `criar_agendamento` | — → `pendente` | Recepcionista cria em nome do cliente |

### Lacunas / Melhorias

- ⚠️ **Sem filtro de data**: lista todos os agendamentos sem separar por dia
- ❌ **`criar_agendamento` pela recepcionista**: implementada no controller mas sem formulário visual dedicado completo

---

## Estados do Agendamento

```
pendente → confirmado → concluído
    ↓           ↓
 cancelado   cancelado
```

| Status | Quem define | Significado |
|--------|------------|-------------|
| `pendente` | Sistema (automático) | Aguardando confirmação |
| `confirmado` | Recepcionista | Confirmado para atendimento |
| `concluído` | Financeiro/Admin | Atendimento realizado |
| `cancelado` | Cliente, Admin ou Recepcionista | Cancelado |

**Regra:** Uma vez `concluído` ou `cancelado`, o agendamento não pode mudar de status.

---

## Critérios de Aceitação

- [ ] Médico inativo não aparece no select de agendamento
- [ ] Slot já ocupado (`pendente`/`confirmado`) não aparece na lista de horários disponíveis
- [ ] Data/hora no passado é rejeitada com mensagem de erro
- [ ] CSRF inválido retorna erro sem processar
- [ ] Cliente não consegue cancelar agendamento de outro cliente via POST manipulado
- [ ] Agendamento com status `concluído` não pode ser cancelado pelo cliente
- [ ] Mudança de especialidade no formulário reseta a lista de médicos
- [ ] Exame inativo não aparece na listagem para agendamento
