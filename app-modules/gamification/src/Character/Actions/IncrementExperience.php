<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Gamification\Character\Models\Character;

class IncrementExperience
{
    public function incrementByTextMessage(string $characterId, string $message): int
    {
        $character = Character::query()->findOrFail($characterId);
        $experience = Character::generateTextExperience($message, $character->level, false);
        $character->increment('experience', $experience);

        return $experience;
    }

    public function incrementByVoiceMessage(string $characterId, VoiceStatesEnum $voiceState): int
    {
        $character = Character::query()->findOrFail($characterId);
        $experience = $voiceState->getExperienceMultiplier() * $character->level;
        $character->increment('experience', $experience);

        return $experience;
    }
}
