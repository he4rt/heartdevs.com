<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Enums\StreakMultiplierEnum;
use He4rt\Gamification\Character\Models\Character;

final readonly class CalculateStreakMultiplierAction
{
    public function execute(string $characterId): float
    {
        $character = Character::query()->findOrFail($characterId);
        $streak = $character->streak ?? 0;

        return StreakMultiplierEnum::fromStreak($streak)->getMultiplier();
    }
}
