<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Concerns;

use Filament\Panel;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\Tenant\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait InteractsWithTenants
{
    /**
     * @return BelongsToMany<Tenant, $this, TenantUser>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')->using(TenantUser::class);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    /**
     * @return array<int, Tenant>|Collection<int, Tenant>
     */
    public function getTenants(Panel $panel): array|Collection
    {
        return $this->tenants;
    }

    /**
     * @return HasMany<Tenant, $this>
     */
    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_id');
    }
}
