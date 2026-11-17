@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Multi-Tenancy

The tenant unit is the **Team** (`He4rt\Identity\Teams\Team`). A `User` belongs to many Teams through the `identity_team_user` pivot.

## Membership

`User` mixes in `He4rt\Identity\Teams\Concerns\InteractsWithTenants`:

- `tenants()` — `BelongsToMany<Team>` over `identity_team_user`.
- `canAccessTenant(Model $tenant)` — membership check.

The `panel-admin` panel is not team-scoped: a super admin sees every Team. A team-scoped panel (`panel-app`) resolves the Team from the URL and checks `canAccessTenant()` itself.

## Data isolation

Tenant-scoped models carry a `team_id` FK and declare a `team()` BelongsTo relationship. There is **no** global tenant scope — scoping is explicit at the query site:

@verbatim
<code-snippet name="Explicit team scoping" lang="php">
Project::query()
    ->where('team_id', $team->id)
    ->get();
</code-snippet>
@endverbatim

## In tests

@verbatim
<code-snippet name="Team-scoped test setup" lang="php">
$team = Team::factory()->create();
$user = User::factory()->create();
$model = SomeModel::factory()->recycle($team)->recycle($user)->create();
</code-snippet>
@endverbatim

Use `->recycle($team)` to propagate the same `team_id` through factory chains.
