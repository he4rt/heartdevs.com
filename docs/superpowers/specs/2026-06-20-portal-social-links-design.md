# Página de redes sociais (link-tree) — `portal`

- **Data:** 2026-06-20
- **Módulo:** `portal` (camada de apresentação)
- **Rota:** `GET /redes` → `heartdevs.com/redes`
- **Status:** aprovado para implementação

## 1. Contexto

A He4rt precisa de uma página **link-tree** que reúna seus links de rede social num
único endereço fácil de divulgar (`heartdevs.com/redes`). A página reaproveita a
**linha de batimento (ECG)** animada que hoje existe na capa do deck de
retrospectiva (`app-modules/portal/resources/views/components/retro/slides/cover.blade.php`),
dando identidade visual coerente com o resto do portal.

Tudo vive no módulo `portal`, seguindo o padrão de página Livewire full-page já
usado em `Homepage` e `CommunityRetrospectivePage` (rota registrada no
`PortalServiceProvider`, `#[Layout]` + `#[Title]`, `render()` retorna uma view
`portal::...`).

### Links (6)

| Rede        | URL                                                | Ícone           |
| ----------- | -------------------------------------------------- | --------------- |
| Discord     | `https://discord.gg/invite/he4rt`                  | `fab-discord`   |
| X (Twitter) | `https://x.com/He4rtDevs`                          | `fab-x-twitter` |
| LinkedIn    | `https://www.linkedin.com/company/he4rt/`          | `fab-linkedin`  |
| WhatsApp    | `https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK` | `fab-whatsapp`  |
| GitHub      | `https://github.com/he4rt`                         | `fab-github`    |
| Site        | `https://heartdevs.com/`                           | `fas-globe`     |

Os ícones de marca vêm de `owenvoke/blade-fontawesome` (já instalado; a navbar
já usa `fab-discord`).

## 2. Decisão de arquitetura — onde os links moram

**Escolhido:** array tipado de DTO `SocialLink` exposto pelo componente Livewire.

São 6 links estáticos que mudam raramente; banco de dados ou config seria
over-engineering (YAGNI). Um `final readonly class SocialLink` mantém a blade
limpa, é testável e segue a convenção do projeto (DTO em vez de array
associativo). Se um dia for preciso editar pelo painel, a migração para
config/DB é local e não quebra a interface da página.

**Descartado:**

- `config/portal.php` — desnecessário para algo praticamente imutável.
- Tabela + recurso Filament — escopo grande demais para o pedido.

## 3. Estrutura de arquivos

```
app-modules/portal/
├── src/
│   ├── DTOs/
│   │   └── SocialLink.php              (NOVO)
│   ├── Livewire/
│   │   └── SocialLinksPage.php         (NOVO)
│   └── PortalServiceProvider.php       (EDIT — registra a rota)
├── resources/views/
│   └── social-links.blade.php          (NOVO)
└── tests/Feature/
    └── SocialLinksPageTest.php          (NOVO)
```

### 3.1 DTO `SocialLink`

`app-modules/portal/src/DTOs/SocialLink.php`, namespace `He4rt\Portal\DTOs`.

```php
final readonly class SocialLink
{
    public function __construct(
        public string $label,
        public string $url,
        public string $icon, // ex.: 'fab-discord'
        public string $accent,
    ) {
        // classe/cor de destaque no hover
    }
}
```

### 3.2 Componente `SocialLinksPage`

`app-modules/portal/src/Livewire/SocialLinksPage.php`, namespace
`He4rt\Portal\Livewire`. Espelha o padrão de `Homepage`:

```php
#[Layout('portal::components.layouts.app')]
#[Title('Nossas redes')]
final class SocialLinksPage extends Component
{
    /** @return list<SocialLink> */
    public function links(): array
    {
        return [
                /* os 6 SocialLink */
            ];
    }

    public function render(): View
    {
        return view('portal::social-links');
    }
}
```

A blade lê os links via `$this->links()`.

### 3.3 Rota (`PortalServiceProvider::boot()`)

```php
Route::get('/redes', SocialLinksPage::class)->name('social-links');
```

### 3.4 View `social-links.blade.php`

Único elemento raiz (`<div>`) por exigência do Livewire. Estrutura: logo He4rt
(`x-he4rt::logo` ou `x-portal::logo`) + tagline + linha ECG animada + stack
vertical de links centralizado (`max-w-[480px]`), botões full-width com ícone de
marca, label e seta; accent da marca no hover. Cada link abre em nova aba
(`target="_blank" rel="noopener noreferrer"`).

A implementação visual usa as skills `/frontend-design` e
`/tailwindcss-development`, reaproveitando componentes/cores do design system
`he4rt`.

## 4. Reaproveitamento da ECG

A ECG da capa (`cover.blade.php`) tem a animação acoplada a `.retro .slide.active`
no `retrospective.css`. Para usá-la fora do deck, copiamos **somente o markup do
`<svg>`** e definimos uma animação própria, scoped e inline na página, que dispara
no carregamento (não depende de `.slide.active`). Isso mantém a feature
autocontida — **sem alterar o layout compartilhado nem o `retrospective.css`**.

**Antes (`cover.blade.php`):**

```html
<svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
    <path
        d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
    />
</svg>
```

**Depois (`social-links.blade.php`):**

```html
<svg class="links-ecg" viewBox="0 0 560 60" preserveAspectRatio="none" aria-hidden="true">
    <path
        d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
    />
</svg>
<style>
    .links-ecg {
        width: 100%;
        max-width: 560px;
        height: 60px;
        display: block;
        margin: 14px auto 6px;
    }
    .links-ecg path {
        fill: none;
        stroke: var(--primary);
        stroke-width: 2.4;
        stroke-linecap: round;
        stroke-linejoin: round;
        filter: drop-shadow(0 0 7px rgba(120, 43, 241, 0.85));
        stroke-dasharray: 1500;
        stroke-dashoffset: 1500;
        animation: links-ecg-draw 1.8s 0.3s ease-out forwards;
    }
    @keyframes links-ecg-draw {
        to {
            stroke-dashoffset: 0;
        }
    }
</style>
```

> A cor da linha usa a variável de tema (`--primary`/`--brand`); confirmar qual
> está disponível no `theme.css` durante a implementação e usar fallback.

## 5. Comportamento esperado (BDD)

```
Cenário: visitante acessa a página de redes
  Dado que acesso GET /redes
  Então recebo 200 e vejo o logo He4rt e a linha de batimento (ECG)
  E vejo os 6 links, cada um com seu ícone de marca
  E cada link aponta para a URL correta e abre em nova aba (target=_blank rel=noopener noreferrer)

Cenário: animação da ECG
  Dado que a página carrega
  Então a linha de ECG se desenha uma única vez (stroke-dashoffset 1500 → 0) e para
  E não depende de .retro .slide.active do deck

Cenário: rota nomeada
  Dado o nome de rota 'social-links'
  Então route('social-links') resolve para /redes

Cenário: responsivo / acessibilidade
  Dado mobile ou desktop
  Então os botões ficam em coluna única centralizada (max-w ~480px)
  E cada link tem texto/aria legível e foco visível
```

## 6. Layout visual (ASCII)

```
┌───────────────────────────────┐
│           [navbar]             │  ← vem do layout app
│                                │
│          ♥ He4rt Devs          │  ← logo
│      Conecte-se com a gente    │  ← tagline
│      ╱╲    ╱╲                   │  ← linha ECG animada (desenha 1x)
│   ──╱  ╲──╱  ╲───────          │
│                                │
│  ┌─────────────────────────┐   │
│  │ (discord)  Discord    →  │   │  ← botões full-width; accent no hover
│  ├─────────────────────────┤   │
│  │ (x)        X / Twitter→  │   │
│  ├─────────────────────────┤   │
│  │ (linkedin) LinkedIn   →  │   │
│  ├─────────────────────────┤   │
│  │ (whatsapp) WhatsApp   →  │   │
│  ├─────────────────────────┤   │
│  │ (github)   GitHub     →  │   │
│  ├─────────────────────────┤   │
│  │ (globe)    Site       →  │   │
│  └─────────────────────────┘   │
└───────────────────────────────┘
```

## 7. Testes

`app-modules/portal/tests/Feature/SocialLinksPageTest.php` (Pest + Livewire):

- `GET /redes` retorna 200.
- `route('social-links')` resolve para `/redes`.
- A página renderiza os 6 labels e contém as 6 URLs corretas.
- Cada link de rede externa usa `target="_blank"` e `rel="noopener noreferrer"`.
- (Opcional) smoke test de ausência de erros JS na rota.

## 8. Fora de escopo (YAGNI)

- Edição dos links via painel admin ou banco de dados.
- Internacionalização dos labels (página é pt-BR, como o resto do portal).
- Métricas/analytics de clique por link.
- Entrada no menu da navbar (a página é divulgada pela URL direta).
