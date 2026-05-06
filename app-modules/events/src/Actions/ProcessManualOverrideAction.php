<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Models\Character;

final readonly class ProcessManualOverrideAction
{
    public function __construct(
        private IncrementExperience $incrementExperience,
    ) {}

    /**
     * Processa override manual
     * - XP base é atribuído SEM multiplicador
     * - Streak NÃO é incrementado nem zerado
     */
    public function execute(EventModel $event, string $userId): void
    {
        $character = Character::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $event->tenant_id)
            ->firstOrFail();

        $baseXp = $event->xp_value;
        $this->incrementExperience->incrementByEventAttendance(
            $character->id,
            $baseXp,
            1.0
        );
    }
}
