<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Observers;

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

class UserObserver
{
    public function creating(User $user): void
    {
        if (blank($user->username)) {
            $user->username = str($user->name)->snake()->toString();
        }
    }

    public function created(User $user): void
    {
        $this->ensureProfileExists($user);
    }

    public function deleted(User $user): void
    {
        $user->address()->delete();
    }

    /**
     * Previously a profile was created as a side-effect of attaching a user to a
     * tenant (TenantUserObserver). With tenancy removed, every newly created user
     * gets a profile here instead. The tenant_id column still exists (dropped in a
     * later phase), so it is sourced from the fixed single-tenant config value when
     * available; unset config (e.g. in tests) simply skips the bootstrap.
     */
    private function ensureProfileExists(User $user): void
    {
        $tenantId = config('he4rt.tenant_id');

        if (!is_string($tenantId) || $tenantId === '') {
            return;
        }

        Profile::ensureExists((string) $user->getKey(), $tenantId);
    }
}
