<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\User;

final readonly class UpdatePassword
{
    public function handle(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }
}
