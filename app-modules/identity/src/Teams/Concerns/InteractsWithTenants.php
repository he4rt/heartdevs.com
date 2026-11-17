<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Concerns;

use He4rt\Identity\Teams\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

trait InteractsWithTenants
{
    /**
     * @return BelongsToMany<Team, $this, Pivot>
     */
    public function tenants(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Team::class,
                'identity_team_user',
                'user_id',
                'team_id'
            )->withTimestamps();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }
}
