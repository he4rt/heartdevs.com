<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Models\Character;

final readonly class ResetStreakAction
{
    public function execute(string $characterId): void
    {
        $character = Character::query()->findOrFail($characterId);
        $character->update(['streak' => 0]);
    }
}
