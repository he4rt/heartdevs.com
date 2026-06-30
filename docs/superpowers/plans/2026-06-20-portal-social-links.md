# Página de redes sociais (link-tree) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publicar uma página link-tree em `GET /redes` com os 6 links de rede social da He4rt, reaproveitando a linha de batimento (ECG) animada da capa do deck de retrospectiva.

**Architecture:** Página Livewire full-page no módulo `portal` (camada de apresentação), seguindo o padrão de `Homepage`/`CommunityRetrospectivePage`. Os links são um array tipado de DTO `SocialLink` exposto pelo componente; a blade itera e renderiza âncoras externas. A ECG é reaproveitada copiando só o markup do `<svg>` e definindo animação própria scoped na página (sem tocar no `retrospective.css`).

**Tech Stack:** Laravel 13, Livewire 4, Filament 5 (icon component), Tailwind v4, Pest 4, `owenvoke/blade-fontawesome` (ícones de marca).

## Global Constraints

- **Conventional commits com escopo de módulo:** `<type>(portal): ...`. Copiar verbatim o padrão do repo.
- **Sem co-autoria:** NUNCA adicionar `Co-Authored-By` nem referência ao Claude nos commits.
- **Camada de apresentação:** `portal` só contém UI. Nenhuma lógica de domínio. Importa do design system `he4rt`; nunca o contrário.
- **DTO sobre array:** dados estruturados usam `final readonly class` sem pacote (convenção do projeto).
- **Pint obrigatório:** rodar `vendor/bin/pint --dirty --format agent` antes de finalizar qualquer alteração em PHP.
- **Ícones de marca:** via `<x-filament::icon icon="fab-...">` (blade-fontawesome já instalado; navbar já usa `fab-discord`).
- **Cor de marca He4rt:** `var(--primary)` = `#782bf1` (definida em `app-modules/he4rt/resources/css/support/themes.css`).
- **Cada teste é programático:** toda mudança acompanha teste Pest e deve passar antes do commit.

---

### Task 1: Rota `/redes` com lista funcional de links

**Files:**

- Create: `app-modules/portal/src/DTOs/SocialLink.php`
- Create: `app-modules/portal/src/Livewire/SocialLinksPage.php`
- Create: `app-modules/portal/resources/views/social-links.blade.php`
- Modify: `app-modules/portal/src/PortalServiceProvider.php`
- Test: `app-modules/portal/tests/Feature/SocialLinksPageTest.php`

**Interfaces:**

- Produces:
    - `He4rt\Portal\DTOs\SocialLink` — `__construct(string $label, string $url, string $icon, string $accent)`, todos `public readonly`.
    - `He4rt\Portal\Livewire\SocialLinksPage::links(): array` — retorna `list<SocialLink>` com os 6 links.
    - Rota nomeada `social-links` → `/redes`.
    - View `portal::social-links` recebe a variável `$links` (`list<SocialLink>`).

- [ ] **Step 1: Escrever os testes que falham**

`app-modules/portal/tests/Feature/SocialLinksPageTest.php`:

```php
<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('responde 200 em /redes', function (): void {
    get('/redes')->assertOk();
});

it('resolve a rota nomeada social-links para /redes', function (): void {
    expect(route('social-links', absolute: false))->toBe('/redes');
});

it('renderiza os seis links com rótulos e URLs corretas', function (): void {
    get('/redes')
        ->assertOk()
        ->assertSee('Discord')
        ->assertSee('X (Twitter)')
        ->assertSee('LinkedIn')
        ->assertSee('WhatsApp')
        ->assertSee('GitHub')
        ->assertSee('Site oficial')
        ->assertSee('https://discord.gg/invite/he4rt')
        ->assertSee('https://x.com/He4rtDevs')
        ->assertSee('https://www.linkedin.com/company/he4rt/')
        ->assertSee('https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK')
        ->assertSee('https://github.com/he4rt')
        ->assertSee('https://heartdevs.com/');
});

it('abre cada link externo em nova aba com rel seguro', function (): void {
    $html = get('/redes')->getContent();

    expect(substr_count($html, 'target="_blank"'))->toBe(6);
    expect(substr_count($html, 'rel="noopener noreferrer"'))->toBe(6);
});
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --compact --filter=SocialLinksPage`
Expected: FAIL — rota `/redes` inexistente (404) e classe `SocialLinksPage` não encontrada.

- [ ] **Step 3: Criar o DTO `SocialLink`**

`app-modules/portal/src/DTOs/SocialLink.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Portal\DTOs;

final readonly class SocialLink
{
    public function __construct(public string $label, public string $url, public string $icon, public string $accent) {}
}
```

- [ ] **Step 4: Criar o componente `SocialLinksPage`**

`app-modules/portal/src/Livewire/SocialLinksPage.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use He4rt\Portal\DTOs\SocialLink;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('portal::components.layouts.app')]
#[Title('Nossas redes')]
final class SocialLinksPage extends Component
{
    /**
     * @return list<SocialLink>
     */
    public function links(): array
    {
        return [
            new SocialLink('Discord', 'https://discord.gg/invite/he4rt', 'fab-discord', '#5865F2'),
            new SocialLink('X (Twitter)', 'https://x.com/He4rtDevs', 'fab-x-twitter', '#FFFFFF'),
            new SocialLink('LinkedIn', 'https://www.linkedin.com/company/he4rt/', 'fab-linkedin', '#0A66C2'),
            new SocialLink('WhatsApp', 'https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK', 'fab-whatsapp', '#25D366'),
            new SocialLink('GitHub', 'https://github.com/he4rt', 'fab-github', '#FFFFFF'),
            new SocialLink('Site oficial', 'https://heartdevs.com/', 'fas-globe', '#782BF1'),
        ];
    }

    public function render(): View
    {
        return view('portal::social-links', [
            'links' => $this->links(),
        ]);
    }
}
```

- [ ] **Step 5: Criar a view mínima funcional**

`app-modules/portal/resources/views/social-links.blade.php` (um único elemento raiz):

```blade
<div class="mx-auto flex w-full max-w-[480px] flex-col gap-3 px-6 py-16">
    @foreach ($links as $link)
        <a
            href="{{ $link->url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="border-outline-low bg-elevation-01dp flex items-center gap-4 rounded-xl border px-5 py-4 text-white"
        >
            <x-filament::icon :icon="$link->icon" class="h-6 w-6 shrink-0" />
            <span class="flex-1 text-left font-medium">{{ $link->label }}</span>
        </a>
    @endforeach
</div>
```

- [ ] **Step 6: Registrar a rota no `PortalServiceProvider`**

Em `app-modules/portal/src/PortalServiceProvider.php`, adicionar o `use` e a rota.

Adicionar ao bloco de imports:

```php
use He4rt\Portal\Livewire\SocialLinksPage;
```

Dentro de `boot()`, logo após a linha `Route::get('/', Homepage::class);`:

```php
Route::get('/redes', SocialLinksPage::class)->name('social-links');
```

- [ ] **Step 7: Rodar os testes e confirmar que passam**

Run: `php artisan test --compact --filter=SocialLinksPage`
Expected: PASS (4 testes verdes).

- [ ] **Step 8: Rodar o Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: arquivos formatados sem erros.

- [ ] **Step 9: Commit**

```bash
git add app-modules/portal/src/DTOs/SocialLink.php \
        app-modules/portal/src/Livewire/SocialLinksPage.php \
        app-modules/portal/resources/views/social-links.blade.php \
        app-modules/portal/src/PortalServiceProvider.php \
        app-modules/portal/tests/Feature/SocialLinksPageTest.php
git commit -m "feat(portal): add /redes social link-tree page"
```

---

### Task 2: Identidade visual — logo, linha ECG animada e acentos de marca

**Files:**

- Modify: `app-modules/portal/resources/views/social-links.blade.php`
- Test: `app-modules/portal/tests/Feature/SocialLinksPageTest.php`

**Interfaces:**

- Consumes: `$links` (`list<He4rt\Portal\DTOs\SocialLink>`) já fornecido pelo componente da Task 1; cada item expõe `label`, `url`, `icon`, `accent`.
- Produces: a view passa a conter o logo He4rt (`x-portal::logo`), uma tagline, o `<svg class="links-ecg">` animado e botões com acento de marca via `style="--accent: ..."`.

- [ ] **Step 1: Adicionar os testes da camada visual (devem falhar)**

Acrescentar ao final de `app-modules/portal/tests/Feature/SocialLinksPageTest.php`:

```php
it('exibe a linha de batimento (ECG) animada', function (): void {
    get('/redes')->assertSee('links-ecg', false);
});

it('exibe a tagline e o acento de marca de cada link', function (): void {
    $html = get('/redes')->getContent();

    expect($html)->toContain('Conecte-se com a comunidade He4rt Devs');
    expect(substr_count($html, '--accent:'))->toBe(6);
});
```

- [ ] **Step 2: Rodar os novos testes e confirmar que falham**

Run: `php artisan test --compact --filter=SocialLinksPage`
Expected: os dois novos testes FALHAM (`links-ecg`, tagline e `--accent:` ainda não existem); os 4 da Task 1 continuam passando.

- [ ] **Step 3: Substituir a view pela versão completa**

Conteúdo final de `app-modules/portal/resources/views/social-links.blade.php`:

```blade
<div class="relative min-h-screen overflow-hidden">
    <style>
        .links-ecg {
            width: 100%;
            max-width: 320px;
            height: 48px;
            display: block;
            margin: 12px auto 28px;
        }
        .links-ecg path {
            fill: none;
            stroke: var(--primary, #782bf1);
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
        .link-card {
            transition:
                border-color 0.2s,
                color 0.2s,
                transform 0.2s,
                background-color 0.2s;
        }
        .link-card:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
            background-color: color-mix(in srgb, var(--accent) 8%, transparent);
        }
        .link-card:hover .link-arrow {
            transform: translateX(4px);
        }
        @media (prefers-reduced-motion: reduce) {
            .links-ecg path {
                animation: none;
                stroke-dashoffset: 0;
            }
            .link-card,
            .link-card:hover {
                transform: none;
            }
        }
    </style>

    <div class="mx-auto flex w-full max-w-[480px] flex-col items-center px-6 py-16">
        <x-portal::logo size="lg" />

        <p class="text-text-medium mt-4 text-center text-sm">Conecte-se com a comunidade He4rt Devs</p>

        <svg class="links-ecg" viewBox="0 0 560 60" preserveAspectRatio="none" aria-hidden="true">
            <path
                d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
            />
        </svg>

        <nav class="flex w-full flex-col gap-3" aria-label="Redes sociais da He4rt">
            @foreach ($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="--accent: {{ $link->accent }}"
                    class="link-card border-outline-low bg-elevation-01dp flex items-center gap-4 rounded-xl border px-5 py-4 text-white"
                >
                    <x-filament::icon :icon="$link->icon" class="h-6 w-6 shrink-0" />
                    <span class="flex-1 text-left font-medium">{{ $link->label }}</span>
                    <x-filament::icon
                        icon="heroicon-s-arrow-up-right"
                        class="link-arrow h-4 w-4 shrink-0 transition-transform"
                    />
                </a>
            @endforeach
        </nav>
    </div>
</div>
```

> Nota de implementação: o `x-portal::logo` já renderiza o coração He4rt (SVG `currentColor`) + wordmark "He4rt Devs", centralizado pelo `items-center` do container. O acento de marca usa `--accent` por link (evita classes Tailwind dinâmicas que o JIT não escaneia). A animação respeita `prefers-reduced-motion`.

- [ ] **Step 4: Rodar todos os testes da página e confirmar que passam**

Run: `php artisan test --compact --filter=SocialLinksPage`
Expected: PASS (6 testes verdes).

- [ ] **Step 5: Verificar visualmente na aplicação**

Usar a skill `/run` (ou abrir a app local) e navegar até `/redes`. Conferir: logo no topo, tagline, a linha ECG desenhando uma única vez no load, os 6 botões com acento de marca no hover e seta deslizando. Conferir responsivo em largura mobile.

- [ ] **Step 6: Rodar Pint e Prettier**

Run: `vendor/bin/pint --dirty --format agent`
(O pre-commit já roda Prettier em `*.blade.php`/`*.css` via lint-staged.)
Expected: sem erros.

- [ ] **Step 7: Commit**

```bash
git add app-modules/portal/resources/views/social-links.blade.php \
        app-modules/portal/tests/Feature/SocialLinksPageTest.php
git commit -m "feat(portal): style /redes page with He4rt logo and animated ECG line"
```

---

## Notas de execução

- **Acabamento visual:** durante a Task 2, ativar as skills `/frontend-design` (direção estética) e `/tailwindcss-development` (classes idiomáticas) para refinar o visual além do esqueleto acima — mantendo os ganchos testados (`links-ecg`, `--accent:`, tagline, `target/rel`).
- **PR:** ao final, abrir PR para a branch `4.x` (base de PRs do projeto), com label `mod:portal` e `type:feat`.
