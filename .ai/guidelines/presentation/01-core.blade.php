# Presentation Layer

Modules with the prefix `panel-*` are the presentation layer: Inertia panels with React and TypeScript. `panel-admin` is the super admin panel at `/admin`. `panel-app` is the panel for every user, at the root. They own UI concerns only: routes, controllers, form requests, read queries, Data classes and pages.

- Domain logic (actions, models, business rules) lives in domain modules, never in a panel. A panel imports domain modules. A domain module never imports a panel, and a panel never imports the other panel.
- A feature is one slice in `src/Features/<Group>/<Feature>/`, mirrored in `resources/js/features/<group>/<feature>/`, the routes and the tests. Only the Home of a panel has no group. When you plan a new feature, suggest its group.
- Controllers use only the resource methods (`index` … `destroy`) or one `__invoke()`, never check anything, and call one action or query. Every endpoint that checks something has its own form request.
- Filament and Livewire were removed. Do not add them back: `tests/Arch/DomainIndependenceTest.php` fails on any reference.
- When a change affects what a user sees in the admin, update `docs/admin/en/` (and `pt_BR` when you can).
- `DESIGN.md` at the root is the visual system of both panels. `.ai/rules/panels-js.md` says how to use it.

The details are in `.ai/rules/panels.md` (PHP), `.ai/rules/panels-js.md` (React) and `.ai/rules/docs-admin.md`. Activate `inertia-react-development` for pages and forms, and `wayfinder-development` for routes.
