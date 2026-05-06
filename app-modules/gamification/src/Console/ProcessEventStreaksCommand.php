<?php

declare(strict_types=1);

namespace He4rt\Events\Console;

use He4rt\Events\Jobs\ResetUnverifiedStreaksJob;
use He4rt\Events\Models\EventModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class ProcessEventStreaksCommand extends Command
{
    protected $signature = 'events:process-streaks';

    protected $description = 'Processa eventos encerrados e reseta streaks de ausentes';

    public function handle(): void
    {
        $now = Date::now();

        $events = EventModel::query()
            ->where('end_at', '<=', $now->copy()->subMinutes(30))
            ->where('active', true)
            ->get();

        $this->info(sprintf('Encontrados %d eventos para processar.', $events->count()));

        foreach ($events as $event) {
            $this->comment(sprintf('Processando evento: %s (ID: %s)', $event->title, $event->id));
            dispatch(new ResetUnverifiedStreaksJob($event->id));
        }
    }
}
