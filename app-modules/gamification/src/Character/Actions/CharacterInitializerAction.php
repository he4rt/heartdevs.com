<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;

final class CharacterInitializerAction
{
    public function ensure(User $user): Character
    {
        return Character::query()->firstOrCreate(
            [
                'user_id' => $user->id,
            ]
        );
    }
}
