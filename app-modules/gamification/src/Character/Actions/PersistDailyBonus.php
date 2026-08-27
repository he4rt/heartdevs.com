<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Exceptions\CharacterException;
use He4rt\Gamification\Character\Models\Character;

final readonly class PersistDailyBonus
{
    public function handle(string $characterId): void
    {
        $character = Character::query()->findOrFail($characterId);

        throw_unless($character->can_claim_daily_bonus, CharacterException::alreadyClaimed($character));

        $character->update(['daily_bonus_claimed_at' => now()]);
    }
}
