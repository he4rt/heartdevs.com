<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Character\Models\Character;
use He4rt\User\Models\User;

final class CharacterInitializerAction
{
    public function ensure(User $user, string $tenantId): Character
    {
        return Character::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ]
        );
    }
}
