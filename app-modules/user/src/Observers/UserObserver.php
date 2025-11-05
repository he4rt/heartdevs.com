<?php

declare(strict_types=1);

namespace He4rt\User\Observers;

use He4rt\User\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        if (blank($user->username)) {
            $user->username = str($user->name)->snake();
        }
    }
}
