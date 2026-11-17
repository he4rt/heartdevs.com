@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Module Architecture

This monorepo uses `internachi/modular`. Each module lives under `app-modules/{kebab-case}/` with namespace `He4rt\{PascalCase}\`.

The namespace is the PascalCase of the slug: `identity` → `He4rt\Identity`, `panel-admin` → `He4rt\PanelAdmin`. Confirm against the module's `composer.json` PSR-4 mapping when in doubt.

## Module types

| Type              | Prefix / Names                         | Contains                                      |
| ----------------- | -------------------------------------- | --------------------------------------------- |
| **Domain**        | `identity`…                            | Business logic: Models, Actions, DTOs, Enums  |
| **Integration**   | `integration-*`                        | External APIs: Transport, OAuth, ETL, Console |
| **Presentation**  | `panel-*`                              | UI: Inertia pages (React), controllers, Data  |

Modules that exist today: `identity` (domain), `panel-admin` (presentation, the super admin panel) and `panel-app` (presentation, the panel for every user). Add rows as new modules are scaffolded.

Presentation modules own UI concerns only. Domain logic belongs in domain modules — see the `presentation/core` guideline.

## Canonical structure

```
app-modules/{module}/
├── composer.json
├── phpstan.neon
├── config/{module}.php                       (optional)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/{en,pt_BR}/                          (optional)
├── routes/{topic}-routes.php                 (optional, auto-discovered)
├── resources/views/                          (optional, presentation only)
├── src/
│   ├── {ModuleName}ServiceProvider.php       <- always at src/ root, never in Providers/
│   ├── Actions/                              <- one business operation each (.ai/rules/actions.md)
│   ├── Models/                               <- and their policies (domain/authorization)
│   ├── Queries/                              <- reads with more than one caller
│   ├── Rules/                                <- validation rules that wrap a domain invariant
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Concerns/
│   ├── Contracts/                            <- interfaces for external services (.ai/rules/services.md)
│   └── ...
├── tests/
│   ├── Feature/
│   └── Unit/
├── CONTEXT.md                                (optional)
└── docs/adr/                                 (optional)
```

## Sub-namespace strategies

**Flat layers** — simple modules:
`src/Actions/`, `src/Models/`, `src/DTOs/`

**Sub-domain grouping** — complex modules (e.g. `identity`):
`src/{SubDomain}/...`

Example: `identity` → `Users/`, `Teams/`, `Permissions/`, each holding its own `Actions/`, `Models/`, `Enums/`, `Casts/`, `Concerns/`.

## ServiceProvider

Always at `src/{ModuleName}ServiceProvider.php`. Minimal pattern:

@verbatim
<code-snippet name="Module ServiceProvider" lang="php">
namespace He4rt\{ModuleName};

class {ModuleName}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'some_class' => SomeClass::class,
            'another_class' => AnotherClass::class,
        ]);
    }
}
</code-snippet>
@endverbatim

Add `mergeConfigFrom()`, `loadTranslationsFrom()`, `Event::listen()`, `Relation::morphMap()` as needed. Check a sibling module's ServiceProvider for the full pattern.

## Module composer.json

@verbatim
<code-snippet name="Module composer.json" lang="json">
{
    "name": "he4rt/{module-slug}",
    "autoload": {
        "psr-4": {
            "He4rt\\{ModuleName}\\": "src/",
            "He4rt\\{ModuleName}\\Database\\Factories\\": "database/factories/",
            "He4rt\\{ModuleName}\\Database\\Seeders\\": "database/seeders/"
        }
    }
}
</code-snippet>
@endverbatim

## Version constraints — mandatory `^1.0.0` style

Every intra-repo `he4rt/*` module dependency (in the root `composer.json` and in any
module's `composer.json`) MUST be declared with the caret style `^1.0.0`. Never use
loose constraints like `>=1`, `*`, `dev-main`, or a truncated `^1.0`.

@verbatim
<code-snippet name="he4rt/* module constraints" lang="json">
{
    "require": {
        // GOOD — caret with full three-part version:
        "he4rt/identity": "^1.0.0",
        "he4rt/panel-admin": "^1.0.0",

        // BAD — loose or truncated constraints:
        "he4rt/identity": ">=1",
        "he4rt/panel-admin": "^1.0",
        "he4rt/identity": "*"
    }
}
</code-snippet>
@endverbatim

This keeps every module pinned to a predictable, SemVer-compatible range and avoids
accidental major upgrades. When you add a new module dependency, match this style.

## Dependency rules

- **Domain** modules never import from Presentation or Integration.
- **Integration** modules may depend on Domain (e.g., Identity for user resolution).
- **Presentation** imports from Domain and Integration, never the reverse.
- Check `CONTEXT-MAP.md` for cross-context constraints.

## Registering a new module — mandatory label sync

Every `app-modules/<slug>/` has exactly one `mod:<slug>` triage label, and vice
versa. Size the work with the `domain/new-modules` guideline before you scaffold one.
When you scaffold a **new** module you MUST, in the same change:

1. **Add a row** to the module table in `workflow/triage-labels` (`mod:<slug>` → `<slug>`).
2. **Create the label on GitHub** so the issue tracker can use it:
   `gh label create "mod:<slug>" --color "c2e0c6" --description "<short description>"`

Do not leave the guideline table and the live GitHub labels out of sync — an issue
that can't be tagged with its module's label means triage and routing break.
