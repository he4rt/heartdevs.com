<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;
use Illuminate\Support\Facades\Date;

final class VerifyAttendanceAction
{
    /**
     * Marca a presença como verificada para um usuário em um evento
     * Isso incrementa o streak e aplica o multiplicador de XP
     */
    public function execute(EventModel $event, string $userId): void
    {
        $event->attendees()->updateExistingPivot($userId, [
            'verified_at' => Date::now(),
        ]);
    }
}
