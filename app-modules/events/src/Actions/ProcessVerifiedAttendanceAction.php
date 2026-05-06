<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Actions\CalculateStreakMultiplierAction;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Actions\IncrementStreakAction;
use He4rt\Gamification\Character\Models\Character;

final readonly class ProcessVerifiedAttendanceAction
{
    public function __construct(
        private VerifyAttendanceAction $verifyAttendance,
        private IncrementStreakAction $incrementStreak,
        private CalculateStreakMultiplierAction $calculateMultiplier,
        private IncrementExperience $incrementExperience,
    ) {}

    /**
     * Processa a verificação de presença com streak e multiplicador
     * Usado por GPS, Discord, Código
     */
    public function execute(EventModel $event, string $userId): void
    {
        $this->verifyAttendance->execute($event, $userId);

        $character = Character::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $event->tenant_id)
            ->firstOrFail();

        $this->incrementStreak->execute($character->id);

        $character->refresh();
        $multiplier = $this->calculateMultiplier->execute($character->id);

        $baseXp = $event->xp_value;
        $this->incrementExperience->incrementByEventAttendance(
            $character->id,
            $baseXp,
            $multiplier
        );
    }
}
