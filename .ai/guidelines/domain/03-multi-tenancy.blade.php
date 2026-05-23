@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Multi-Tenancy

Filament-native tenancy. Tenants are Discord guilds / organizations.

## Resolution

Filament resolves the tenant from the URL slug (`/{panel}/{tenant-slug}/…`). Both Admin and App panels configure `->tenant(Tenant::class, slugAttribute: 'slug')` in their panel providers.

## Data isolation

Models carry a `tenant_id` FK with a `tenant()` BelongsTo relationship — each model defines its own, there is no shared trait.

The Admin panel registers `ApplyTenantScopes` tenant middleware that adds `whereBelongsTo($tenant)` global scopes to models listed in `config('panel-admin.tenant_scoped_models')`. Filament also auto-scopes any model that has a Resource registered in a tenant-enabled panel.

## In tests

@verbatim
<code-snippet name="Tenant-scoped test setup" lang="php">
$tenant = Tenant::factory()->create();
$user = User::factory()->create();
$model = SomeModel::factory()->recycle($tenant)->recycle($user)->create();
</code-snippet>
@endverbatim

Use `->recycle($tenant)` to propagate the tenant ID through factory chains.
