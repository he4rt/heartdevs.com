<?php

declare(strict_types=1);

namespace He4rt\Tenant\Models\Concerns;

use Filament\Panel;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

trait InteractsWithTenants
{
    /**
     * @return BelongsToMany<Tenant, $this, Pivot>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users');
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    /**
     * @return array<Tenant> | Collection
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
