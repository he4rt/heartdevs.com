---
paths:
  - 'app/Support/FeatureFlags/**'
  - 'app-modules/*/src/Features/*.php'
---

# Feature flags

A feature flag turns a feature on for some teams or users before everyone, and turns it off without a deploy. `laravel/pennant` stores the state. `App\Support\FeatureFlags` wraps it, and the admin switches it on **System → Feature flags**. The plan is `docs/plans/2026-11-12-feature-flags.md`.

## Declare a flag

- A flag is a `final` class in the `src/Features/` folder of the module that owns the feature: `He4rt\Billing\Features\NewCheckout`. It implements `FeatureFlag` and uses `InteractsWithFeatureState`. Add `SharedWithFrontend` only when a page reads it.
- The module registers it in its service provider: `resolve(FeatureRegistry::class)->register([NewCheckout::class])`. An unregistered flag is not on the admin screen.
- `key()` is kebab case and never changes, because Pennant stores the state under it. Rename the class freely, never the key.
- `scope()` is `Global` (the gate alone), `Team` or `User`. A flag has one scope.
- A flag has no constructor arguments and no `resolve()`. Every flag starts off everywhere, with its gate closed. There is no default on and no percentage rollout.

## Read a flag

- Read it through the flag class: `NewCheckout::activeFor($team)`, `BetaDashboard::activeFor($user)`, `MaintenanceBanner::active()`. The call checks the gate and the scope. A read for the wrong scope throws `FeatureScopeMismatch`.
- Never call `Feature::active()`, `Feature::for()` or the `features` methods of Pennant outside `App\Support\FeatureFlags`. They skip the gate.
- A whole area (a screen, a group of endpoints) reads the flag on the server: in `authorize()`, in a query or in the navigation. A component reads the shared `features` prop with `useFeature('new-checkout')`.

## A flag never replaces a permission

The access to a feature behind a flag is always the gate of the flag **and** the RBAC ability. Check the flag first and answer not found, so a user without the feature never learns that the area exists. Then check the permission and answer forbidden.

```php
// Avoid: the flag is the only check
public function authorize(#[CurrentUser] User $user): bool
{
    return BetaDashboard::activeFor($user);
}

// Good: the flag, then the permission
public function authorize(#[CurrentUser] User $user): Response
{
    if (!BetaDashboard::activeFor($user)) {
        return Response::denyAsNotFound();
    }

    return $user->can(PermissionsEnum::View->buildPermissionFor(Dashboard::class))
        ? Response::allow()
        : Response::deny();
}
```

- On a page, `useFeature()` only shows or hides a component. A `can` prop still decides what the user may do.

## Write a flag

- Change the state only through the actions: `OpenFeatureGate`, `CloseFeatureGate`, `EnableFeatureFlag`, `DisableFeatureFlag`. They write the activity log and skip a write that changes nothing. Never call `activate()`, `deactivate()` or `forget()` of Pennant directly.
- Disabling a flag for a team or a user deletes its row. Closing the gate keeps the rows, so opening it again brings the same teams and users back.
- Keep `StoredOnlyDatabaseDriver` as the store. Pennant's own database driver stores a row on the first read of every scope, which breaks `.ai/domain/04-data`.
- A new table that relates to a team or a user gets nothing from here. `DeleteUser` and `DeleteTeam` already call `ForgetFeatureFlags`.

## Remove a flag

When a rollout ends and the feature is on for everyone for good, remove the flag in one change: the class, its registration, every check and `useFeature()` call, and its rows (a migration that deletes the `feature_flags` rows with its `name`).

## Tests

- Use the fixture flags in `tests/Fixtures/FeatureFlags` (`NewCheckout` per team, `BetaDashboard` per user, `MaintenanceBanner` global), or an anonymous class for a one-off case. Register them in `beforeEach()`.
- Set the state with the actions, not with Pennant: `resolve(OpenFeatureGate::class)->handle(new NewCheckout)`.
- A feature behind a flag has a test for each layer: flag off (404), flag on without the permission (403), and both (allowed).
