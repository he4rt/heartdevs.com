<?php

declare(strict_types=1);

namespace He4rt\Profile\Queries;

use He4rt\Identity\User\Models\User;

final readonly class FindPublicProfileUser
{
    public function handle(string $username): ?User
    {
        return User::query()
            ->where('username', $username)
            ->whereNull('banned_at')
            ->first();
    }
}
