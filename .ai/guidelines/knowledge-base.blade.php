# Knowledge Base Documentation This project uses `guava/filament-knowledge-base` to provide an embedded documentation
system inside the Filament admin panel. Documentation is written as Markdown files and rendered in the sidebar. ##
Documentation Structure All documentation files live in `docs/admin/{lang}/` following this structure: ```
docs/admin/{lang}/ ├── introduction.md # Top-level page (ungrouped) ├── getting-started.md # Group definition (type:
group) │ └── getting-started/ │ ├── navigating-the-panel.md │ ├── dashboard.md │ └── profile.md ├── users.md # Group
definition (type: group) │ └── users/ │ ├── managing-users.md │ ├── roles.md │ ├── teams.md │ └── authentication.md └──
system.md # Group definition (type: group) └── system/ ├── activity-logs.md ├── emails.md └── configuration.md ``` ###
Rules - Maximum **3 levels** of nesting. - Group files (directories) require a matching `.md` file at the same level
with `type: group` in the front matter. - All documentation files require YAML front matter with at least `title`,
`icon`, and `order`. - Use `heroicon-o-*` icons from the Heroicons outlined set. ### Front Matter Format ```yaml ---
title: Page Title icon: heroicon-o-document order: 1 --- ``` For group files, add `type: group`: ```yaml --- title:
Group Name icon: heroicon-o-folder order: 2 type: group --- ``` ## Keeping Documentation in Sync When making changes to
the codebase that affect user-facing behavior, you MUST update the related documentation in `docs/admin/en/`. This
includes: - **Adding a new resource or page** — Create a corresponding doc file under the appropriate group directory. -
**Changing navigation groups or labels** — Update `docs/admin/en/{group}.md` and its children to match. - **Adding,
removing, or renaming form fields** — Update the relevant doc page that describes the resource. - **Changing
authentication or authorization behavior** — Update `docs/admin/en/users/authentication.md` and
`docs/admin/en/users/roles.md`. - **Modifying system features** (activity logs, emails, config) — Update the
corresponding file under `docs/admin/en/system/`. - **Adding or changing translations** — Ensure doc content reflects
the new labels. If no existing doc page covers the changed feature, create a new one under the appropriate group and add
it with the correct front matter (title, icon, order). ## Key Files - `app/Filament/Plugins/BetterKnowledgeBase.php` —
Plugin that builds the documentation sidebar navigation. Adds a "Documentation" nav item to the admin panel and
overrides the sidebar with the doc tree when viewing docs. - `config/filament-knowledge-base.php` — KB plugin
configuration (cache TTL, icons, model). - `resources/views/vendor/filament-knowledge-base/livewire/help-menu.blade.php`
— Custom help menu popover for contextual per-resource documentation. - `lang/en/knowledge_base.php` and
`lang/pt_BR/knowledge_base.php` — Translations for the KB UI. ## Contextual Help (HasKnowledgeBase) Resources can
implement `Guava\FilamentKnowledgeBase\Contracts\HasKnowledgeBase` to link contextual documentation that appears in the
sidebar help popover: ```php use Guava\FilamentKnowledgeBase\Contracts\HasKnowledgeBase; class UserResource extends
Resource implements HasKnowledgeBase { public static function getDocumentation(): array { return [
'users.managing-users', 'users.roles', ]; } } ``` When adding `HasKnowledgeBase` to a resource, the documentation IDs
follow the pattern `{group}.{file-slug}` matching the file path under `docs/admin/en/`.
