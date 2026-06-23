# Specs de Desenvolvimento — Clínica Saúde & Bem-Estar

Documentação funcional e técnica do sistema. Use como referência ao implementar novas features, corrigir bugs, ou revisar comportamento esperado.

---

## Documentos

| Arquivo | Módulo | Cobertura |
|---------|--------|-----------|
| [autenticacao.md](autenticacao.md) | Login, cadastro, logout, reset de senha, guards de acesso | RF-AUTH-01 a RF-AUTH-05 |
| [agendamentos.md](agendamentos.md) | Consultas, exames, cancelamentos, APIs JSON de horários/médicos | RF-AGEND-01 a RF-AGEND-06 |
| [medicos.md](medicos.md) | CRUD médicos, horários de atendimento, especialidades, exames | RF-MED-01, RF-ESPEC-01, RF-EXAM-01 |
| [financeiro.md](financeiro.md) | Ciclo de pagamento, repasses aos médicos, relatórios | RF-FIN-01 a RF-FIN-04 |
| [paineis.md](paineis.md) | Dashboard cliente, admin, médico e recepcionista | RF-PAINEL-01 a RF-PAINEL-04 |
| [publico.md](publico.md) | Homepage, especialidades, médicos, exames, sobre, login/cadastro | RF-PUB-01 a RF-PUB-09 |

---

## Status Geral por Módulo

| Módulo | Status | Notas |
|--------|--------|-------|
| Autenticação | ✅ Completo | Reset de senha não usa tabela `redefinicao_senha` |
| Agendamento de consultas | ✅ Completo | Sem reagendamento, sem notificação |
| Agendamento de exames | ✅ Completo | Sem verificação de capacidade/horário |
| CRUD Médicos | ✅ Completo | Sem upload de foto pela UI |
| Horários de atendimento | ✅ Completo | Sem feriados/férias |
| CRUD Especialidades | ✅ Completo | Ícone não é configurável |
| CRUD Exames | ✅ Completo | Sem categoria de exame |
| Financeiro — conclusão | ✅ Completo | Recálculo no fechamento pode surpreender |
| Financeiro — repasse | ✅ Completo | Sem reversão, sem PDF |
| Painel Cliente | ✅ Completo | Sem edição de perfil, sem troca de senha |
| Painel Admin | ✅ Completo | Sem CRUD de recepcionistas |
| Painel Médico | ✅ Completo | Sem KPIs financeiros |
| Painel Recepcionista | ⚠️ Parcial | `novo_agendamento` sem UI completa |
| Páginas públicas | ✅ Completo | Conteúdo institucional estático |
| Relatórios | ⚠️ Parcial | View existe, profundidade a verificar |

---

## Gaps Prioritários (Backlog)

### Alta Prioridade

1. **Edição de perfil do cliente** — cliente não tem como alterar email, telefone ou senha  
   Arquivo a criar: `backend/controllers/perfil_controller.php`  
   View: nova ação em `painel_cliente.php?acao=editar_perfil`

2. **Upload de foto do médico** — campo `medicos.foto` existe, mas a UI não tem `<input type="file">`  
   Requer: criação de diretório `assets/fotos_medicos/`, validação de tipo/tamanho no backend

3. **CRUD de recepcionistas** — não há forma de criar/editar recepcionistas pela UI do admin  
   Arquivo a criar: ação `recepcionista_form` em `painel_admin.php`

4. **Sessão idle expira silenciosamente** — `SESSION_TIMEOUT = 3600` definido mas não aplicado  
   Arquivo a editar: `backend/config/config.php` — adicionar verificação de `$_SESSION['última_atividade']`

### Média Prioridade

5. **Máscaras de input para CPF e telefone** — `000.000.000-00` e `(00) 00000-0000` via JS  
   Arquivo a editar: `assets/js/app.js` + `cadastro/criar_conta.php`

6. **Reagendamento** — cliente só pode cancelar e criar novo; falta ação `alterar_agendamento`  
   Arquivo a criar: nova ação em `agendamento_controller.php`

7. **Pré-seleção de médico no agendamento via URL** — botão "Agendar com este médico" em `medico_detalhe.php` não pre-seleciona  
   Arquivo a editar: `painel_cliente.php` — ler `?id_medico=N` da URL e pre-popular o form

8. **Prazo mínimo de cancelamento** — cliente pode cancelar minutos antes  
   Arquivo a editar: `agendamento_controller.php` — adicionar verificação de janela mínima (ex: 2h antes)

### Baixa Prioridade

9. **Filtros de agendamentos no painel cliente** — sem filtro por status ou data

10. **Exportação CSV/PDF** — nenhuma listagem tem exportação

11. **Feriados e férias de médico** — sistema não bloqueia datas especiais

12. **Tempo estimado e popularidade reais** — `obter_tempo_estimado()` e `eh_popular()` são hardcoded

13. **Viewer de logs no admin** — logs em arquivo mas sem interface de visualização

---

## Regras de Negócio Globais

| Regra | Descrição |
|-------|-----------|
| Senha mínima | 6 chars (constante `PASSWORD_MIN_LENGTH`) |
| Bcrypt cost | 10 (constante em `gerar_hash_senha()`) |
| Rate limit login | 5 tentativas / 15 min por IP |
| Rate limit reset | 3 tentativas / hora por telefone |
| Session timeout | 3600s (definido mas não aplicado ativamente) |
| Session regeneration | A cada 5 minutos de atividade |
| Cancelamento de agendamento | Apenas status `pendente` ou `confirmado` |
| Percentual médico padrão | 70% do valor da consulta |
| Percentual exame padrão | 0% (exame não gera repasse automático) |
| Agendamento mínimo | 1 hora no futuro (`validar_data_agendamento()`) |
| Exclusão de médico | Bloqueada se há agendamentos futuros ativos |
| Exclusão de especialidade | Bloqueada se há médicos vinculados |
| Exclusão de exame | Bloqueada se há agendamentos históricos |
| CPF | Validação com dígitos verificadores mod-11 |
| Idade mínima de cadastro | 18 anos |

---

## Convenções de Identificadores nos Specs

- `RF-XXX-NN` = Requisito Funcional (módulo + número)
- ✅ = Implementado e funcional
- ⚠️ = Parcialmente implementado ou com ressalvas
- ❌ = Não implementado / lacuna identificada
