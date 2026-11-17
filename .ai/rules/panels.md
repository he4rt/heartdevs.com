---
paths:
  - 'app-modules/panel-*/routes/**'
  - 'app-modules/panel-*/src/**'
  - 'app-modules/panel-*/tests/**'
---

# Panels (`panel-*`)

`panel-admin` (the super admin panel, at `/admin`) and `panel-app` (the panel for every user, at `/`) are the presentation layer. These rules cover their PHP code. `.ai/rules/panels-js.md` covers their React code.

## Layers

- A panel owns routes, controllers, form requests, queries, the Data classes of its pages, and React code.
- A list that only this panel shows lives in the feature's `Queries/`. A list with a second caller (the other panel, an export, an API or MCP tool) lives in the `Queries/` folder of its domain module, and the panel imports it.
- Domain modules own models, actions, enums, policies and business rules. A panel imports them. A domain module never imports a panel, and a panel never imports the other panel.
- Code that changes domain state is an action in the domain module that owns the state. Never write one in a panel. When no module clearly owns it, ask.

## Structure

Group by feature, and every feature by its group:

```text
app-modules/panel-admin/
├── routes/admin/
│   ├── home.php
│   └── setup/users.php
├── src/
│   ├── Auth/
│   ├── Features/
│   │   ├── Home/
│   │   └── Setup/
│   │       └── Users/
│   │           ├── Http/
│   │           │   ├── Controllers/
│   │           │   │   ├── UserController.php
│   │           │   │   └── UserImpersonationController.php
│   │           │   └── Requests/
│   │           │       ├── ListUsersRequest.php
│   │           │       └── DeleteUserRequest.php
│   │           ├── Queries/ListUsers.php
│   │           ├── Data/UserRowData.php
│   │           └── Rules/
│   ├── Navigation/
│   └── Shared/Http/Middleware/
├── resources/js/
│   ├── features/setup/users/
│   │   ├── pages/
│   │   ├── components/
│   │   └── hooks/
│   └── components/ hooks/ layouts/ lib/
└── tests/Feature/Setup/Users/
```

- A feature lives in `src/Features/<Group>/<Feature>/`. The Home (dashboard) of a panel is the one feature without a group: `src/Features/Home/`.
- The groups today:

| Panel | Group | Features |
| --- | --- | --- |
| `panel-admin` | `Setup` | `Users`, `Teams`, `Roles` |
| `panel-admin` | `System` | `Activity`, `Mails`, `Settings`, `Docs` |
| `panel-admin` | `Account` | `Profile` |
| `panel-app` | `Account` | `Settings`, `TwoFactorSetup` |

- When you plan a new feature, suggest the group it goes in, and say why. When no group fits, suggest a new one and ask before you create it.
- A group is a stable bucket, not a copy of a sidebar heading. Do not move a feature when only its heading changes.
- `Auth`, `Shared` and the panel infrastructure (`Navigation`, `Providers`) stay at the top of `src/`.
- The namespace follows the folder: `He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers\UserController`.
- The group and feature folders are the same in `src/`, `resources/js/features/<group>/<feature>/` (kebab case), `routes/<panel>/<group>/<feature>.php` and `tests/Feature/<Group>/<Feature>/`. The route loader of the panel requires every file in the group folders too.
- A page name has no group: `features/setup/users/pages/index.tsx` is the page `users/index`, because `app.tsx` finds a page by its `/<feature>/pages/<page>.tsx` suffix. When two features in different groups have the same name, their page names keep the group.
- A part of a feature with its own `Queries` or `Data` is a sub-feature with the same structure: `src/Features/Setup/Teams/Members/Http/Controllers/`.
- A set of controllers without its own `Queries` or `Data` is a subfolder of `Http/Controllers`, mirrored in `Http/Requests`: `Http/Controllers/Password/` and `Http/Requests/Password/`.
- Code that two features of a panel use goes in `src/Shared/` or in the panel's `resources/js/{components,hooks,lib}/`, also when both features are in one group. Code that both panels use goes in `app/` or the root `resources/js/` (alias `@`). A feature never imports the internals of another feature.
- Admin guest pages live in `features/auth/` and build from `auth.tsx`, their own entry. `app.tsx` leaves them out, so the login page never downloads the admin app.
- A visit that goes to the other panel, or out of the admin guest pages, is a full page visit: answer with `Inertia::location()`, not a redirect. The other bundle cannot render the page.

### Moving and splitting code

- Keep every URL and route name when you move a feature or split a controller. Pages import routes by name through Wayfinder. Point the route at the new controller.
- After a move, search the repository for the old class and file names as strings, and run `bun run routes` and `php artisan typescript:transform`. A stale string or generated type fails only at runtime.

## Controllers

- Public methods are only `index`, `create`, `store`, `show`, `edit`, `update` and `destroy`.
- Never add a custom method. Find the resource that the endpoint creates, changes or removes, and give it a controller. The action that the controller calls keeps the business name:

| Today | Becomes | Calls |
| --- | --- | --- |
| `ProfileController@updatePassword` | `ProfilePasswordController@update` | `UpdatePassword` |
| `ProfileController@destroyOtherSessions` | `BrowserSessionController@destroy` | `SignOutOtherSessions` |
| `ImpersonateUserController` (a verb) | `UserImpersonationController@store` | `StartImpersonation` |

- When an endpoint really does not fit a resource, use an invokable controller with one `__invoke()`, named after the thing: `ExportController`.
- Names are nouns: `UserImpersonationController`, not `ImpersonateUserController`.
- A controller is a `final readonly class`.
- A controller stays thin: it takes the form request, calls one action or query, and returns a response. Inject the action or query as a method parameter. Never call `app()` or `resolve()`.
- A controller never checks anything: no `abort()`, `abort_if()`, `abort_unless()`, `throw_if()`, `throw_unless()`, `$request->validate()`, `Validator::make()`, `ValidationException`, `Gate::authorize()` or `Gate::denies()`.
- A controller can read an ability to build a `can` prop (`'canCreate' => $user->can('create', Team::class)`). It never enforces one.
- A controller can catch an exception that an action throws, to turn it into a toast or a field error with `back()->withErrors()`. It never throws one.
- Answer a write with the `App\Http\Controllers\Concerns\FlashesToast` trait: `$this->back('Team deleted')`, or `$this->back($message, ToastType::Error)`.
- Keep the form request parameter also when the method does not read it. Its `authorize()` runs only when the container resolves it.

```php
// Avoid: the controller authorizes and checks
public function destroy(Request $request, User $user, DeleteUser $deleteUser): RedirectResponse
{
    Gate::authorize('delete', $user);
    abort_if($user->is($request->user()), 403, 'You cannot delete your own account.');
    // ...
}

// Good: the form request checks, the controller calls the action
public function destroy(DeleteUserRequest $request, User $user, DeleteUser $deleteUser): RedirectResponse
{
    try {
        $deleteUser->handle($user);
    } catch (UserOwnsTeams $userOwnsTeams) {
        return $this->back($userOwnsTeams->getMessage(), ToastType::Error);
    }

    return $this->back('User deleted');
}
```

## Form requests

- Endpoint authorization goes in `authorize()` and validation in `rules()`. This includes `index` and `show`.
- Create one form request for each endpoint that checks anything, and never share one between endpoints.
- Name it after the method it serves: `index` → `List…Request`, `show` → `Show…Request`, `create` → `New…Request`, `store` → `Create…Request`, `edit` → `Edit…Request`, `update` → `Update…Request`, `destroy` → `Delete…Request`.
- Every form request extends `FormRequest` directly and declares its own `authorize()`, also when it only allows. Never extend another form request. When two endpoints share rules, repeat them. `.ai/rules/validation.md` says how to write a rule that Laravel does not have.
- When several endpoints of a feature find the same record in the URL, share the lookup through one `Resolves<Record>` trait in the feature's `Http/Requests`. The trait finds the record scoped to its parent, keeps it in a typed property and returns a `Response`. It never has `rules()` and never reads input. Each request still declares its own `authorize()` and calls the trait.
- Take the route model and the actor as parameters: `authorize(#[RouteParameter('user')] User $target, #[CurrentUser] User $actor)`. The same works in `rules()`. Other methods read `$this->route('user')` and assert its type.
- Type-hint dependencies on `authorize()` and `rules()`, because the container calls both. Never call `app()` in a request or a request trait.
- A request finds the records it needs in `authorize()` and keeps them in typed properties. An accessor such as `$request->team()` only reads those properties or `safe()` input. It never looks anything up.
- When hiding something should look as if it does not exist (a record of another team), return `Response::denyAsNotFound()` instead of `false`.
- After validation, read input through `$this->safe()` or `validated()`, never through `input()`, `string()` or `boolean()` on the request.
- A request hands its input to the action in one of two shapes:
    - _One or two values → accessors._ One method per value, named after what it returns: `$request->language()`. The controller passes them to `handle()`.
    - _Three or more values, or a group that two actions take → `toDto()`._ The request builds one DTO, and the controller passes `$request->toDto()`. Never another name for it, such as `teamData()`.
- `toDto()` carries only validated input. The route model and a record that the request found (`$request->owner()`) stay separate arguments of `handle()`.
- The DTO is a `final readonly class` with typed properties, in the `DTOs/` folder of the sub-domain that owns the action. A panel never declares one.
- A request with no input has no `toDto()` and no accessors. It exists for `authorize()`.
- In `rules()`, validated input does not exist yet. A lookup there reads `input()`, checks its type first and memoizes with `once()`.
- Use `Rule::enum(...)->only([...])` for a subset of an enum, not `Rule::in()` with its values.
- Never name a request method after a member of `Request`, such as `path()`, `format()` or `content()`. The override breaks framework code that calls the member at runtime, and static analysis does not report it. Name the accessor after what it returns: `csvPath()`.
- `validated()` rebuilds a nested list in the order of its rules, not of the input. When the order matters, restore it with `->sortKeys()`.

```php
// Avoid: an abstract base request that endpoints extend
abstract class UserTargetRequest extends FormRequest
{
    public function authorize(): Response
    {
        return $this->target()->hasRole(Roles::SuperAdmin) ? Response::denyAsNotFound() : Response::allow();
    }
}

final class ShowUserRequest extends UserTargetRequest {}

// Good: each request states its own check and takes the route model as a parameter
final class DeleteUserRequest extends FormRequest
{
    public function authorize(#[RouteParameter('user')] User $target, #[CurrentUser] User $actor): Response
    {
        if ($target->is($actor)) {
            return Response::deny('You cannot delete your own account.');
        }

        return $actor->can('delete', $target) ? Response::allow() : Response::deny();
    }
}
```

### Authorization and validation are two layers

| Layer | Answers | Failure | Lives in |
| --- | --- | --- | --- |
| `authorize()` | Can this actor touch the record in the URL? | 403 or 404 | the form request, through the domain policies |
| `rules()` | Is the body of the request valid? | field error (302 back, or 422) | the form request, through rule objects |

- A check on the route model (a super admin, a member of another team, a trashed record) is authorization.
- A check on a value or an id in the body (a role the actor may grant, a team of the actor, a unique email) is validation, and goes in a rule object.

### Rule objects

The rules for custom validation rules are in `.ai/rules/validation.md`.

## Authorization

The model is in the `domain/authorization` guideline. In a panel:

- A panel never defines authorization: no policy classes, no `Gate::define()` and no `can:` middleware.
- `authorize()` is the one place in a panel that enforces a check, with `$user->can()`.
- A `can` prop (in a Data class, a query or a controller) reads the same ability only to show or hide UI. When `authorize()` checks more than one ability, the prop calls the same domain method, so a button never shows for a request that is refused.
- The panel boundary stays in route group middleware: `EnsureSuperAdmin`, `AuthenticateSession` and `RequireTwoFactorSetup` in the admin, `auth`, `verified` and `RequireTwoFactorSetup` in the app. An endpoint with no check beyond that boundary needs no form request for authorization.

## Data classes

- A page reads a Data class (`spatie/laravel-data`), never a model. After you change one, run `php artisan typescript:transform`. Pages read the type as `He4rt.PanelAdmin.Features.Setup.Users.Data.UserRowData`.
- Dates leave as ISO 8601 strings, and enums as `EnumOptionData`.

Code written before these rules does not follow them all yet: flat features, custom controller methods, checks in controllers. Do not copy it. Follow these rules in new code, and move old code in a change of its own.
