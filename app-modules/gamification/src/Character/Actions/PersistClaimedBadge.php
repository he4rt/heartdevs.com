<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Models\Character;

final readonly class PersistClaimedBadge
{
    public function handle(string $characterId, int|string $badgeId): void
    {
        $character = Character::query()->findOrFail($characterId);
        $character->badges()
            ->attach($badgeId, [
                'claimed_at' => now(),
            ]);
    }
}
