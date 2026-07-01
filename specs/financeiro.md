# Spec: Módulo Financeiro

**Módulo:** `backend/controllers/financeiro_controller.php`  
**View:** `backend/views/financeiro.php`  
**Status geral:** ✅ Parcialmente implementado — lógica core existe, mas UI de relatórios e conciliação é limitada

---

## Visão Geral

O módulo financeiro gerencia o ciclo de pagamento de agendamentos:
1. Atendimento realizado → agendamento marcado como `concluído`
2. Valor calculado → registrado em `agendamentos.valor_total` e `agendamentos.valor_medico`
3. Clínica consolida repasses por período → gera registro em `pagamentos_medicos`
4. Repasse marcado como `pago_medico`

---

## Modelo de Dados Financeiros

### Tabela `agendamentos` (campos financeiros)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `valor_total` | DECIMAL(10,2) | Valor cobrado do paciente |
| `valor_medico` | DECIMAL(10,2) | Parte do médico (% do total) |
| `status_pagamento` | ENUM | `pendente`, `pago_clinica`, `pago_medico` |
| `data_realizacao` | DATE | Data em que o atendimento ocorreu |

### Tabela `pagamentos_medicos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_medico` | INT FK | Médico que receberá o repasse |
| `periodo_inicio` | DATE | Início do período de consolidação |
| `periodo_fim` | DATE | Fim do período de consolidação |
| `total_consultas` | INT | Quantidade de atendimentos no período |
| `total_bruto` | DECIMAL(10,2) | Soma de `valor_total` |
| `total_medico` | DECIMAL(10,2) | Soma de `valor_medico` |
| `data_pagamento` | DATE | Quando o repasse foi efetivado |
| `status` | VARCHAR | `pendente`, `pago` |

---

## RF-FIN-01: Marcar Atendimento como Realizado

**Ator:** Admin  
**Ação:** `financeiro_controller.php?acao=marcar_realizado`

### Fluxo

1. Admin seleciona agendamento com status `pendente` ou `confirmado`
2. POST com `id_agendamento`
3. Validar CSRF + `require_admin()`
4. Verificar que agendamento existe e tem status válido
5. Recalcular `valor_total` (busca valor atual do médico/exame — não usa valor armazenado)
6. Recalcular `valor_medico = valor_total × percentual_medico / 100`
7. Definir `data_realizacao = data atual (Y-m-d)`
8. Atualizar `status = 'concluído'`, `status_pagamento = 'pendente'`

### Regra de negócio crítica

- O valor é **recalculado no momento da conclusão** com base no percentual atual do médico — não usa o valor registrado no agendamento original
- Isso significa que se o percentual do médico mudar entre o agendamento e a conclusão, o valor final é o mais recente

### Lacunas / Melhorias

- ⚠️ **Recálculo no momento da conclusão pode surpreender**: se admin mudar o valor da consulta do médico após o agendamento, o valor final será diferente do contratado — considerar congelar o valor no momento do agendamento
- ❌ **Sem confirmação de pagamento do paciente**: não há controle se o paciente pagou (apenas controle do repasse ao médico)
- ❌ **Sem diferenciação de formas de pagamento**: dinheiro, cartão, convênio — tudo é tratado igual

---

## RF-FIN-02: Gerar Repasse ao Médico

**Ator:** Admin  
**Ação:** `financeiro_controller.php?acao=gerar_pagamento`

### Fluxo

1. Admin seleciona médico + período (data_inicio, data_fim)
2. POST para controller
3. Validar CSRF + `require_admin()`
4. Buscar todos os agendamentos do médico no período com:
   - `status = 'concluído'`
   - `status_pagamento IN ('pendente', 'pago_clinica')`
5. Somar `valor_total` → `total_bruto`
6. Somar `valor_medico` → `total_medico`
7. Contar registros → `total_consultas`
8. INSERT em `pagamentos_medicos`
9. UPDATE `agendamentos.status_pagamento = 'pago_medico'` para todos os incluídos

### Regras de negócio

- Apenas agendamentos `concluídos` entram no cálculo — `pendente` e `confirmado` são ignorados
- Agendamentos com `status_pagamento = 'pago_medico'` não são incluídos novamente
- Um mesmo agendamento não pode aparecer em dois repasses
- Período pode ser qualquer range de datas (sem restrição de ser mensal)

### Lacunas / Melhorias

- ⚠️ **Sem preview antes de confirmar**: admin gera o pagamento sem ver o resumo antes
- ⚠️ **Sem reversão de pagamento**: uma vez gerado o repasse (`pago_medico`), não há ação de estorno na UI
- ❌ **Sem comprovante de repasse**: não há geração de PDF/recibo para o médico
- ❌ **Sem controle bancário**: sistema não registra dados bancários do médico para transferência

---

## RF-FIN-03: Visualização Financeira (Dashboard)

**View:** `backend/views/financeiro.php`  
**Ator:** Admin

### Dados exibidos (esperados)

- Total arrecadado no mês/período selecionado
- Total a repassar aos médicos
- Lista de repasses pendentes por médico
- Histórico de repasses realizados
- Agendamentos concluídos sem pagamento registrado

### Lacunas / Melhorias

- ⚠️ **Dashboard financeiro é básico**: exibe dados mas sem gráficos ou projeções
- ❌ **Sem exportação**: não é possível exportar relatório financeiro para Excel/PDF
- ❌ **Sem conciliação por forma de pagamento**: não há rastreamento de como o paciente pagou

---

## RF-FIN-04: Relatórios

**View:** `backend/views/relatorios.php`  
**Ator:** Admin

### Lacunas / Melhorias

- ⚠️ **Módulo de relatórios existe mas pode estar incompleto**: view de relatórios existe, mas os dados e filtros disponíveis precisam ser verificados
- ❌ **Sem relatório de ocupação**: taxa de ocupação de slots do médico por período
- ❌ **Sem relatório por especialidade**: faturamento e agendamentos por especialidade
- ❌ **Sem gráficos**: dados apenas em tabela

---

## Ciclo Financeiro Completo

```
Agendamento criado → status_pagamento: 'pendente'
      ↓
Recepcionista confirma → status: 'confirmado' (pagamento ainda 'pendente')
      ↓
Admin marca como realizado → status: 'concluído', data_realizacao: hoje
      ↓
Admin gera repasse → pagamentos_medicos INSERT
      ↓
agendamentos.status_pagamento: 'pago_medico'
```

---

## Percentuais de Repasse

| Campo | Default | Descrição |
|-------|---------|-----------|
| `medicos.percentual_medico` | 70 | % do valor da consulta que vai ao médico |
| `medicos.percentual_exame` | 0 | % do valor do exame que vai ao médico |

Fórmula: `valor_medico = valor_total × percentual / 100`

---

## Critérios de Aceitação

- [ ] Agendamento só pode ser marcado como realizado se tiver status `pendente` ou `confirmado`
- [ ] `data_realizacao` é preenchida automaticamente com a data atual
- [ ] Repasse gerado não inclui agendamentos com `status_pagamento = 'pago_medico'`
- [ ] Após gerar repasse, todos os agendamentos incluídos têm `status_pagamento` atualizado
- [ ] Valores são calculados com 2 casas decimais
- [ ] Período do repasse pode cruzar meses diferentes
