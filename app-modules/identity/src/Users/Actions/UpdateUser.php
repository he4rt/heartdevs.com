<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\DTOs\UserData;
use He4rt\Identity\Users\User;

final readonly class UpdateUser
{
    public function handle(User $user, UserData $data): void
    {
        $user->update($data->toDatabase());
    }
}
