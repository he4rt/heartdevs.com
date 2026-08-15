<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Observers;

use He4rt\Identity\User\Enums\Role;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

class UserObserver
{
    public function creating(User $user): void
    {
        if (blank($user->username)) {
            $user->username = str($user->name)->snake()->toString();
        }

        if ($this->isConfiguredAdmin($user->username) && !in_array($user->role, [Role::Staff, Role::Compliance], strict: true)) {
            $user->role = Role::Staff;
        }
    }

    public function created(User $user): void
    {
        $this->ensureProfileExists($user);
    }

    public function forceDeleted(User $user): void
    {
        $user->address()->delete();
    }

    /**
     * Previously a profile was created as a side-effect of attaching a user to a
     * tenant (TenantUserObserver). With tenancy removed, every newly created user
     * gets a profile here instead.
     */
    private function ensureProfileExists(User $user): void
    {
        Profile::ensureExists((string) $user->getKey());
    }

    private function isConfiguredAdmin(string $username): bool
    {
        return in_array($username, User::configuredAdminUsernames(), strict: true);
    }
}
