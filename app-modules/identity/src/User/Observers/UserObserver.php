<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Observers;

use He4rt\Identity\User\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        if (blank($user->username)) {
            $user->username = str($user->name)->snake()->toString();
        }
    }

    public function deleted(User $user): void
    {
        $user->address()->delete();
    }
}
