<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\DTOs\NewUserData;
use He4rt\Identity\Users\User;

final readonly class CreateUser
{
    public function handle(NewUserData $data): User
    {
        return User::query()->create($data->toDatabase());
    }
}
