# Design System — Clínica Saúde & Bem-Estar

Referência visual e de componentes do projeto. Sempre use as variáveis CSS definidas — nunca valores hardcoded.

---

## Identidade Visual

**Nome:** Clínica Saúde & Bem-Estar  
**Tom:** Saúde, confiança, profissionalismo — verde-azulado (#007b83) como cor primária.

---

## Paleta de Cores (Variáveis CSS)

Todas definidas em `:root` em [assets/css/estilo.css](assets/css/estilo.css).

### Cores Primárias
| Variável | Valor | Uso |
|----------|-------|-----|
| `--cor-primaria` | `#007b83` | Teal — identidade da clínica, links, bordas, ícones |
| `--cor-primaria-escura` | `#005a60` | Hover de elementos primários |
| `--cor-primaria-clara` | `#e3f3f4` | Fundos de ícones, badges informativos |

### Cores de Destaque
| Variável | Valor | Uso |
|----------|-------|-----|
| `--cor-destaque` | `#27ae60` | Verde — sucesso, confirmação, preços, CTAs |
| `--cor-destaque-escura` | `#1e8449` | Hover de elementos de destaque |
| `--cor-destaque-clara` | `#e6f7ed` | Fundo de badges de sucesso, stat-cards |

### Cores Semânticas
| Variável | Valor | Uso |
|----------|-------|-----|
| `--cor-perigo` | `#dc3545` | Vermelho — erro, cancelamento, exclusão |
| `--cor-perigo-escura` | `#c82333` | Hover de botões de perigo |
| `--cor-perigo-clara` | `#fbe7e9` | Fundo de badges de erro |
| `--cor-aviso` | `#f0ad4e` | Laranja — avisos, pendências |
| `--cor-aviso-clara` | `#fff3cd` | Fundo de badges de aviso |

### Neutros
| Variável | Valor | Uso |
|----------|-------|-----|
| `--cor-texto` | `#2c3e50` | Texto principal |
| `--cor-texto-claro` | `#5b6b73` | Texto secundário, labels, subtítulos |
| `--cor-fundo` | `#eef7f6` | Background geral da página |
| `--cor-card` | `#ffffff` | Fundo de cards e modais |
| `--cor-borda` | `#e0e0e0` | Bordas de inputs e divisores |

---

## Tipografia

| Variável | Fonte | Peso | Uso |
|----------|-------|------|-----|
| `--fonte-titulo` | Poppins (fallback: Segoe UI, Tahoma) | 500–700 | Títulos h1–h4, botões, labels de nav |
| `--fonte-texto` | Inter (fallback: Segoe UI, Tahoma) | 400–600 | Parágrafos, labels, tabelas, inputs |

**Regra:** Elementos com `font-family: var(--fonte-titulo)`:  
`h1, h2, h3, h4, .meu-btn, nav a, .btn, .btn-action, .btn-agendar, .btn-solicitar, .btn-sobre, .admin-btn`

---

## Espaçamento & Dimensões

| Variável | Valor | Uso |
|----------|-------|-----|
| `--raio` | `8px` | Border-radius padrão (inputs, badges, toasts) |
| `--raio-grande` | `16px` | Border-radius de cards e painéis |
| `--nav-altura` | `80px` | Altura da navbar — usada em `padding-top: body` e `top: flash-container` |
| `--sombra` | `0 4px 15px rgba(0,0,0,0.08)` | Sombra padrão de cards |
| `--sombra-hover` | `0 8px 25px rgba(0,0,0,0.15)` | Sombra no hover de cards interativos |

---

## Ícones

**Biblioteca:** Font Awesome 6.5.1 via CDN (incluído no `header.php`).

**Padrão de uso:** `<i class="fa-solid fa-NomeDoIcone"></i>`

Mapeamento de especialidades e exames em `backend/utils/funcoes_gerais.php`:
- `obter_icone_especialidade($nome)` → retorna `<i class="fa-solid fa-..."></i>`
- `obter_icone_exame($nome)` → retorna `<i class="fa-solid fa-..."></i>`

**Nunca use emojis diretamente no HTML** — use Font Awesome para consistência visual.

---

## Componentes

### Cards

**`.destaque-card`** — Cards da homepage (especialidades, valores)
```html
<a href="#" class="destaque-card">
    <div class="icone"><i class="fa-solid fa-heart"></i></div>
    <h3>Título</h3>
    <p>Descrição...</p>
</a>
```
Comportamento: `translateY(-6px)` no hover + sombra mais intensa + borda-topo muda de teal para verde.

---

**`.especialidade-card` / `.exame-card`** — Grid de especialidades e exames
```html
<div class="especialidade-card">
    <div class="card-icone"><i class="fa-solid fa-stethoscope"></i></div>
    <h3>Cardiologia</h3>
    <p>Descrição...</p>
    <div class="card-meta">
        <span class="card-tempo"><i class="fa-regular fa-clock"></i> 30 min</span>
    </div>
    <a href="#" class="btn btn-agendar">Agendar Consulta</a>
</div>
```
Efeito especial no hover: borda gradiente animada (`teal → verde`) via `::before` pseudo-elemento.

---

**`.stat-card`** — Cards de estatísticas (painéis admin/recepcionista)
```html
<div class="stat-card stat-card-warning">
    <div class="stat-icone pulse"><i class="fa-solid fa-clock"></i></div>
    <h3>Pendentes</h3>
    <div class="stat-number">12</div>
    <span class="stat-variacao positivo">↑ 3 hoje</span>
</div>
```
Variantes: `stat-card-warning` (laranja), `stat-card-success` (verde), `stat-card-primario` (teal).  
Animação `pulse` disponível na classe `.stat-icone.pulse`.

---

**`.info-card`** — Cards de perfil do usuário
```html
<div class="info-grid">
    <div class="info-card">
        <div class="info-card-icone" data-tooltip="Email"><i class="fa-solid fa-envelope"></i></div>
        <div class="info-card-corpo">
            <span class="info-card-label">Email</span>
            <span class="info-card-valor"><?= htmlspecialchars($email) ?></span>
        </div>
    </div>
</div>
```
Suporta tooltip via `data-tooltip="texto"`.

---

### Flash Toasts

Gerenciados automaticamente via PHP + JS. Surgem no canto superior direito, desaparecem em 5s com barra de progresso.

```php
// PHP (controller):
set_flash_message('sucesso', 'Agendamento realizado com sucesso!');
set_flash_message('erro', 'Erro ao processar o agendamento.');

// Template (view) — incluir no topo do conteúdo:
$flash_sucesso = get_flash_message('sucesso');
$flash_erro    = get_flash_message('erro');
```

Classes de tipo: `.flash-toast.flash-sucesso` (verde), `.flash-toast.flash-erro` (vermelho).  
Definição em [assets/css/components/flash.css](assets/css/components/flash.css).

---

### Botões

| Classe | Estilo | Uso |
|--------|--------|-----|
| `.btn` / `.meu-btn` | Gradiente teal→verde | CTA principal |
| `.btn-agendar` | Outline teal → sólido no hover | Agendar consulta |
| `.btn-solicitar` | Outline verde → sólido no hover | Solicitar exame |
| `.btn-secondary` / `.link-button` | Cinza flat | Ação secundária |
| `.btn-danger` / `.btn-cancelar` | Vermelho | Cancelar, excluir |

Botões de submit em forms têm ícone de cadeado `🔒` automático via CSS `::before`.  
Estado de carregamento: adicionar classe `.carregando` via JS → mostra spinner.

---

### Formulários

**Autenticação:** wrapper `.auth-page` (fundo gradiente) + `.auth-card` (card branco centralizado, max 420px).

**Painéis:** inputs dentro de `.painel-conteudo form` com borda `--cor-borda`, `border-radius: 5px`.

**Agrupamentos de campos:** usar `.form-grupo` com `.form-grupo-titulo`.

**Erro de campo:** usar `.campo-erro` abaixo do input (exibe ícone ⚠️ automaticamente via CSS).

Focus state de inputs:
- Auth: `border-color: --cor-destaque` + `box-shadow: 0 0 5px rgba(verde, 0.2)`

---

### Badges de Status

Definidos em `funcoes_gerais.php` → `get_classe_status($status)`:

| Status | Classe CSS | Cor |
|--------|-----------|-----|
| `pendente` | `badge-warning` | Laranja |
| `confirmado` | `badge-success` | Verde |
| `cancelado` | `badge-danger` | Vermelho |
| `concluído` | `badge-info` | Azul |

Uso em tabelas:
```php
<span class="badge <?= get_classe_status($agendamento['status']) ?>">
    <?= get_status_agendamento($agendamento['status']) ?>
</span>
```

---

## Layout

### Páginas Públicas

Estrutura: `navbar fixa (80px) → main → footer`.

```
body { padding-top: var(--nav-altura); }
```

Seções usam container `.container` com `max-width: 1200px; margin: 0 auto; padding: 0 20px`.

Grid de cards: `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;`

### Painéis Autenticados

Layout de duas colunas: sidebar esquerda fixa + conteúdo à direita.

```
.painel-layout { display: flex; gap: 24px; min-height: calc(100vh - var(--nav-altura)); }
.painel-sidebar { width: 260px; flex-shrink: 0; position: sticky; top: calc(var(--nav-altura) + 20px); }
.painel-conteudo { flex: 1; min-width: 0; }
```

### Admin Menu

Dashboard admin usa grid de ícones de ação (`9 itens`):
```
.admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }
```

---

## Responsividade

| Breakpoint | Comportamento |
|-----------|---------------|
| `< 768px` | Tabelas substituídas por cards (via `display: none` na tabela, `display: grid` nos cards) |
| `< 768px` | Sidebar colapsa ou vai para topo |
| `< 480px` | Flash toasts ocupam largura total da tela |

**Regra:** Nunca criar breakpoints fixos para itens novos sem checar o comportamento responsivo em mobile real. Preferir `auto-fit + minmax` para grids.

---

## Animações

| Nome | Uso |
|------|-----|
| `flash-entrar` | Toast de notificação (slide-in from right) |
| `flash-progresso` | Barra de progresso do toast (5s linear) |
| `stat-pulse` | Ícone de stat-card com animação de pulse (atenção a pendências) |
| `girar` | Spinner de carregamento em botões de submit |

---

## Padrões ao Criar Novas Views

1. **Sempre usar variáveis CSS** — nunca `color: #007b83` diretamente
2. **Cards interativos** devem ter `transition: transform 0.3s ease, box-shadow 0.3s ease` + hover `translateY(-Xpx)`
3. **Flash message** com `get_flash_message()` no topo do conteúdo de cada view
4. **Estado vazio** — quando não há dados, exibir mensagem amigável (ex: `<p class="texto-vazio">Nenhum agendamento encontrado.</p>`)
5. **Ícones**: sempre Font Awesome, nunca emojis no HTML
6. **Outputs de banco**: sempre `<?= htmlspecialchars($valor) ?>` — nunca `<?= $valor ?>`
7. **Preços**: formatar com `formatar_valor($preco)` → `R$ 1.234,56`
8. **Datas**: formatar com `formatar_data($data)` → `DD/MM/YYYY` ou `formatar_data_hora()` → `DD/MM/YYYY HH:MM`

---

## Arquivos CSS

| Arquivo | Conteúdo |
|---------|----------|
| [assets/css/estilo.css](assets/css/estilo.css) | Design system, variáveis, reset, navbar, botões, hero, seções públicas |
| [assets/css/components/cards.css](assets/css/components/cards.css) | Todos os tipos de card (destaque, valor, especialidade, exame, stat, info) |
| [assets/css/components/menu.css](assets/css/components/menu.css) | Sidebar dos painéis, menu admin |
| [assets/css/components/forms.css](assets/css/components/forms.css) | Auth-card, form-grupo, inputs, botões de submit |
| [assets/css/components/flash.css](assets/css/components/flash.css) | Flash toasts flutuantes |
| [assets/css/components/agendamentos.css](assets/css/components/agendamentos.css) | Cards e tabelas de agendamentos nos painéis |
| [assets/css/components/medicos.css](assets/css/components/medicos.css) | Grid de médicos na página pública |
