@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Knowledge Base Documentation

This project uses `guava/filament-knowledge-base` for embedded docs inside the Filament admin panel. Docs are Markdown files rendered in the sidebar.

## Structure

All files live in `docs/admin/{lang}/`:

```
docs/admin/{lang}/
├── introduction.md
├── getting-started.md              (type: group)
│   └── getting-started/
│       ├── navigating-the-panel.md
│       ├── dashboard.md
│       └── profile.md
├── users.md                        (type: group)
│   └── users/
│       ├── managing-users.md
│       ├── roles.md
│       ├── teams.md
│       └── authentication.md
└── system.md                       (type: group)
    └── system/
        ├── activity-logs.md
        ├── emails.md
        └── configuration.md
```

### Rules

- Maximum **3 levels** of nesting.
- Group directories require a matching `.md` file at the same level with `type: group` in front matter.
- All files require YAML front matter: `title`, `icon`, `order`.
- Use `heroicon-o-*` icons (Heroicons outlined set).

### Front Matter

@verbatim
<code-snippet name="Page front matter" lang="yaml">
---
title: Page Title
icon: heroicon-o-document
order: 1
---
</code-snippet>
@endverbatim

For groups, add `type: group`:

@verbatim
<code-snippet name="Group front matter" lang="yaml">
---
title: Group Name
icon: heroicon-o-folder
order: 2
type: group
---
</code-snippet>
@endverbatim

## Keeping Docs in Sync

When changes affect user-facing behavior, update `docs/admin/en/`:

- **New resource/page** — add a doc file under the appropriate group.
- **Changed nav groups/labels** — update the group `.md` and children.
- **Added/removed/renamed form fields** — update the resource's doc page.
- **Auth/authorization changes** — update `users/authentication.md` and `users/roles.md`.
- **System features** (logs, emails, config) — update under `system/`.

## Key Files

- `app/Filament/Plugins/BetterKnowledgeBase.php` — sidebar navigation builder
- `config/filament-knowledge-base.php` — plugin config (cache TTL, icons, model)
- `resources/views/vendor/filament-knowledge-base/livewire/help-menu.blade.php` — contextual help popover
- `lang/{en,pt_BR}/knowledge_base.php` — KB UI translations

## Contextual Help (HasKnowledgeBase)

Resources can implement `HasKnowledgeBase` for per-resource sidebar help:

@verbatim
<code-snippet name="HasKnowledgeBase implementation" lang="php">
use Guava\FilamentKnowledgeBase\Contracts\HasKnowledgeBase;

class UserResource extends Resource implements HasKnowledgeBase
{
    public static function getDocumentation(): array
    {
        return ['users.managing-users', 'users.roles'];
    }
}
</code-snippet>
@endverbatim

Doc IDs follow `{group}.{file-slug}` matching paths under `docs/admin/en/`.
