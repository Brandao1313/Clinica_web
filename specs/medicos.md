# Spec: Médicos, Especialidades & Exames

**Módulos:** `backend/controllers/medico_controller.php`, `especialidade_controller.php`, `exame_controller.php`  
**Status geral:** ✅ Implementado (CRUD completo)

---

## RF-MED-01: CRUD de Médicos

**Ator:** Admin  
**Arquivo:** `backend/controllers/medico_controller.php`  
**Views:** `medico_listar.php`, `medico_form.php`

### Campos do cadastro de médico

| Campo | Tipo | Validação |
|-------|------|-----------|
| Nome | VARCHAR(100) | Obrigatório, mín 3 chars |
| CRM | VARCHAR(20) | Obrigatório, único no sistema |
| Especialidade | FK → especialidades | Obrigatório, deve ser especialidade ativa |
| Email | VARCHAR(150) | Único, usado como login do médico |
| Telefone | VARCHAR(20) | Numérico, 10–11 dígitos |
| Valor da consulta | DECIMAL(10,2) | > 0 |
| % Repasse ao médico | INT 0–100 | Padrão 70 |
| % Repasse de exame | INT 0–100 | Padrão 0 |
| Bio | TEXT | Opcional, exibida no perfil público |
| Foto | VARCHAR(255) | Opcional, URL/path da foto |
| Senha | VARCHAR(255) | Hash bcrypt, usada para login |

### Fluxo de criação

1. Admin preenche formulário em `medico_form.php`
2. POST para `medico_controller.php?acao=salvar_medico`
3. Validar CSRF + `require_admin()`
4. Verificar unicidade de CRM e email
5. **INSERT em `clientes`** primeiro (tipo = `'medico'`, cria conta de login):
   - email do médico
   - senha hash
   - nome
   - CPF fake gerado automaticamente (médico não tem CPF obrigatório no sistema)
   - Telefone do médico
6. **INSERT em `medicos`** com `id_cliente` = ID do cliente recém-criado
7. Flash message + redirect

### Fluxo de edição

- Atualiza campos em `medicos`
- Se `id_cliente` preenchido (médico tem conta): sincroniza nome e senha em `clientes` também

### Fluxo de exclusão

**Pré-condições para poder excluir:**
- Médico não pode ter agendamentos com status `pendente` ou `confirmado` no futuro
- Se tiver: retorna erro "Médico possui agendamentos futuros — inative-o primeiro"

**Ao excluir:**
- `DELETE FROM medicos WHERE id = ?`
- FK `ON DELETE SET NULL` em `agendamentos` → agendamentos históricos ficam com `id_medico = NULL`
- FK `ON DELETE CASCADE` em `horarios_atendimento` → horários são removidos

### Toggle de status (ativar/inativar)

- Ação `toggle_status_medico`
- Alterna campo `ativo` entre 0 e 1
- Médico inativo **não aparece** em buscas públicas, listagem de especialidades, ou seleção de agendamento
- Médico inativo **não consegue fazer login** (verificação em `logar.php`)

### Lacunas / Melhorias

- ⚠️ **Sem upload de foto**: campo `foto` existe no banco mas a UI não tem input de upload — admin precisa inserir URL manualmente
- ⚠️ **CPF fake gerado automaticamente**: médico criado tem um CPF inválido na tabela `clientes`, o que pode causar problemas se a unicidade de CPF for checada
- ❌ **Sem edição de perfil pelo próprio médico**: médico só pode alterar senha via `medico_perfil_controller.php`, não outros dados
- ❌ **Sem recepcionista criável via UI**: não há CRUD de recepcionistas no painel admin

---

## RF-MED-02: Horários de Atendimento

**Arquivo:** `medico_controller.php` (ação `salvar_horarios`)  
**View:** `horarios_form.php`

### Modelo de dados

Tabela `horarios_atendimento`:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_medico` | INT FK | Médico dono do horário |
| `dia_semana` | INT 0–6 | 0=domingo, 1=segunda ... 6=sábado |
| `hora_inicio` | TIME | Início do expediente |
| `hora_fim` | TIME | Fim do expediente |
| `intervalo_minutos` | INT 5–240 | Duração de cada slot/consulta |
| `ativo` | TINYINT | 1=ativo, 0=inativo |

### Regras de negócio

- Um médico pode ter múltiplas janelas no mesmo dia (ex: 08:00–12:00 e 14:00–17:00)
- `hora_inicio` deve ser anterior a `hora_fim`
- `intervalo_minutos` entre 5 e 240 minutos
- Não pode haver sobreposição de janelas no mesmo dia para o mesmo médico
- Ao salvar: **DELETE todos os horários do médico** + **INSERT** dos novos (substituição completa)
- Slots gerados por `gerar_slots_horario($inicio, $fim, $intervalo)` — o último slot não pode ultrapassar `hora_fim`

### Geração de slots (exemplo)

```
hora_inicio = 09:00, hora_fim = 12:00, intervalo = 30min
→ slots: ["09:00", "09:30", "10:00", "10:30", "11:00", "11:30"]
```

### Lacunas / Melhorias

- ⚠️ **Sem feriados**: sistema não considera feriados nacionais ou clínica fechada
- ⚠️ **Sem férias de médico**: não há como bloquear período específico de ausência
- ❌ **Sem validação visual de sobreposição no formulário**: usuário pode tentar salvar janelas sobrepostas e recebe erro somente após submit

---

## RF-ESPEC-01: CRUD de Especialidades

**Arquivo:** `backend/controllers/especialidade_controller.php`  
**Views:** `especialidade_listar.php`, `especialidade_form.php`  
**Ator:** Admin

### Campos

| Campo | Validação |
|-------|-----------|
| Nome | Obrigatório, único no sistema |
| Descrição | Opcional, texto livre |
| Ativo | Boolean, padrão true |

### Regras de negócio

- Nome único — tentativa de duplicata retorna erro específico
- Especialidade não pode ser excluída se houver:
  - Médicos vinculados (`FOREIGN KEY RESTRICT`)
  - Agendamentos históricos vinculados
- Para remover: inativar médicos vinculados primeiro, depois excluir
- Especialidade inativa não aparece na listagem pública nem no formulário de agendamento

### Lacunas / Melhorias

- ❌ **Sem ícone customizável via UI**: ícone exibido publicamente é determinado por `obter_icone_especialidade()` no servidor, sem campo no banco
- ❌ **Sem ordem de exibição**: especialidades são listadas por ordem de criação

---

## RF-EXAM-01: CRUD de Exames

**Arquivo:** `backend/controllers/exame_controller.php`  
**Views:** `exame_listar.php`, `exame_form.php`  
**Ator:** Admin

### Campos

| Campo | Validação |
|-------|-----------|
| Nome | Obrigatório, único no sistema |
| Descrição | Opcional, texto livre |
| Preço | DECIMAL(10,2), obrigatório, > 0 |
| Ativo | Boolean, padrão true |

### Regras de negócio

- Nome único
- Exame não pode ser excluído se houver agendamentos históricos vinculados
- Exame inativo não aparece na listagem pública nem no formulário de solicitação
- Preço do exame é copiado para `agendamentos.valor_total` no momento da solicitação — mudança posterior de preço não afeta agendamentos existentes

### Lacunas / Melhorias

- ❌ **Sem categoria de exame**: todos os exames numa lista única sem agrupamento (laboratoriais, imagem, etc.)
- ❌ **Sem duração estimada configurável**: tempo exibido vem de `obter_tempo_estimado()` que é hardcoded

---

## Critérios de Aceitação

### Médicos
- [ ] CRM duplicado é rejeitado com mensagem de erro específica
- [ ] Médico inativo não aparece no select de agendamento
- [ ] Médico com agendamentos futuros não pode ser excluído
- [ ] Alteração de senha do médico via admin sincroniza a conta de login

### Horários
- [ ] Slot de 09:00 com intervalo de 30 min em janela 09:00–10:00 gera [09:00, 09:30]
- [ ] Dois médicos diferentes podem ter o mesmo horário em dias diferentes sem conflito
- [ ] Salvar horários substitui completamente os anteriores

### Especialidades
- [ ] Especialidade com médico ativo não pode ser excluída
- [ ] Especialidade inativa não aparece na homepage nem no agendamento

### Exames
- [ ] Preço zero ou negativo é rejeitado
- [ ] Exame inativo não aparece na listagem pública
