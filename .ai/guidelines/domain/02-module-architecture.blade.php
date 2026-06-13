@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Module Architecture

This monorepo uses `internachi/modular`. Each module lives under `app-modules/{kebab-case}/` with namespace `He4rt\{PascalCase}\`.

Exception: `he4rt` module uses namespace `He4rt\Core`.

## Module types

| Type              | Prefix / Names                         | Contains                                      |
| ----------------- | -------------------------------------- | --------------------------------------------- |
| **Domain**        | `identity`, `moderation`, `economy`…   | Business logic: Models, Actions, DTOs, Enums  |
| **Integration**   | `integration-*`, `bot-discord`         | External APIs: Transport, OAuth, ETL, Console |
| **Presentation**  | `panel-*`, `portal`                    | UI: Filament Resources, Livewire, Blade, CSS  |

Presentation modules own UI concerns only. Domain logic belongs in domain modules — see `presentation/core` guideline.

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
│   ├── Actions/
│   ├── Models/
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Concerns/
│   ├── Contracts/
│   └── ...
├── tests/
│   ├── Feature/
│   └── Unit/
├── CONTEXT.md                                (optional)
└── docs/adr/                                 (optional)
```

## Sub-namespace strategies

**Flat layers** — simple modules (economy, profile):
`src/Actions/`, `src/Models/`, `src/DTOs/`

**Sub-domain grouping** — complex modules (identity, moderation, activity):
`src/{SubDomain}/Actions/`, `src/{SubDomain}/Models/`

Examples: `identity` → `Auth/`, `User/`, `Tenant/`, `ExternalIdentity/`; `moderation` → `Cases/`, `Classification/`, `Enforcement/`, `Appeals/`.

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

## Dependency rules

- **Domain** modules never import from Presentation or Integration.
- **Integration** modules may depend on Domain (e.g., Identity for user resolution).
- **Presentation** imports from Domain and Integration, never the reverse.
- Check `CONTEXT-MAP.md` for cross-context constraints.
