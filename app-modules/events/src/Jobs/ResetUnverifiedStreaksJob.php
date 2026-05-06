<?php

declare(strict_types=1);

namespace He4rt\Events\Jobs;

use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Actions\ResetStreakAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class ResetUnverifiedStreaksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $eventId,
    ) {}

    public function handle(): void
    {
        $event = EventModel::query()->findOrFail($this->eventId);

        // Ignora eventos cancelados
        if ($event->active === false) {
            return;
        }

        // Busca usuários com status = attending (pré-confirmados)
        // que NÃO têm verified_at (não verificaram presença)
        $unverifiedAttendees = DB::table('events_attendees')
            ->where('event_id', $this->eventId)
            ->whereNotNull('status') // Que tenham status (estão confirmados)
            ->whereNull('verified_at') // E não verificaram presença
            ->get();

        $resetStreakAction = new ResetStreakAction();

        foreach ($unverifiedAttendees as $attendee) {
            // Busca o character do usuário para este tenant
            $character = DB::table('characters')
                ->where('user_id', $attendee->user_id)
                ->where('tenant_id', $event->tenant_id)
                ->first();

            if ($character) {
                $resetStreakAction->execute($character->id);
            }
        }
    }
}
