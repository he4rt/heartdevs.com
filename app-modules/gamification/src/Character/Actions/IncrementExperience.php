<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Gamification\Character\Models\Character;

class IncrementExperience
{
    public function incrementByTextMessage(string $characterId, string $message, float $multiplier = 1.0): int
    {
        $character = Character::query()->findOrFail($characterId);
        $experience = Character::generateTextExperience($message, $character->level, false);
        $finalExperience = (int) ($experience * $multiplier);
        $character->increment('experience', $finalExperience);

        return $finalExperience;
    }

    public function incrementByVoiceMessage(string $characterId, VoiceStatesEnum $voiceState, float $multiplier = 1.0): int
    {
        $character = Character::query()->findOrFail($characterId);
        $experience = $voiceState->getExperienceMultiplier() * $character->level;
        $finalExperience = (int) ($experience * $multiplier);
        $character->increment('experience', $finalExperience);

        return $finalExperience;
    }

    public function incrementByEventAttendance(string $characterId, int $baseXp, float $multiplier = 1.0): int
    {
        $character = Character::query()->findOrFail($characterId);
        $finalExperience = (int) ($baseXp * $multiplier);
        $character->increment('experience', $finalExperience);

        return $finalExperience;
    }
}
