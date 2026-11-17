# Authorization

Permissions are data: `spatie/laravel-permission` stores them, `app-modules/<module>/config/rbac.php` gives each role its defaults, `php artisan sync:permissions` writes them, and a super admin edits them on the Roles screen.

- A policy lives in the domain module next to its model (`He4rt\Identity\Teams\TeamPolicy`), bound with `#[UsePolicy]`. It only translates a model and an ability into the permission name, plus the state of the record: `$user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(Team::class)) && !$team->trashed()`.
- A rule about a relation (a member of a team, the owner of a record) is a method on the model in the domain module, such as `$team->isMember($user)`. The policy, the form request and the UI props call that method. Never repeat the rule.
- The `authorize()` method of a form request is the only place that enforces a check, with `$user->can()`. Controllers, actions and models never authorize.
- A `can` prop only shows or hides UI. It reads the same ability as `authorize()` and never replaces it.
- A presentation module never defines authorization: no policies, no `Gate::define()`, no `can:` middleware.

The rule for panels (`.ai/rules/panels.md`) has the form request patterns.
