---
paths:
  - 'docs/admin/**'
---

# Admin documentation

The admin docs are Markdown files in `docs/admin/{locale}/`. `panel-admin` shows them at `/admin/docs/{path}` (`He4rt\PanelAdmin\Features\System\Docs\Queries\DocsLibrary`).

```text
docs/admin/{en,pt_BR}/
├── introduction.md
├── users.md                        (type: group)
│   └── users/
│       ├── managing-users.md
│       ├── roles.md
│       └── teams.md
└── system.md                       (type: group)
    └── system/
        ├── emails.md
        └── settings.md
```

- A group is a file with `type: group` and a folder with the same name. Its page lists the pages in the folder under its own text.
- The front matter has `title` and `order` (lower first). `icon` is optional.
- English (`en`) is the source. A page that `pt_BR` does not have shows in English with a notice.
- The renderer strips raw HTML. Use Markdown and GitHub tables only.
- Path segments use `[a-z0-9-]`, so a file name is lowercase kebab-case.

```yaml
---
title: Roles & Permissions
order: 2
---
```

## Keep the docs in step with the screens

- A new screen gets a page under the matching group, and the screen links to it with `HelpLink` in the `PageHeader` actions: `<PageHeader heading="Teams" actions={<HelpLink path="users/teams" />} />`.
- A change to the fields or labels of a form changes the page of that screen.
- A change to authentication or authorization changes `users/authentication.md` and `users/roles.md`.
