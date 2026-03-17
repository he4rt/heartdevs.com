<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Models\Character;

final readonly class ManageReputation
{
    public function handle(string $characterId, string $type): void
    {
        $character = Character::query()->findOrFail($characterId);

        match ($type) {
            'increment' => $character->increment('reputation'),
            'decrement' => $character->decrement('reputation'),
        };
    }
}
