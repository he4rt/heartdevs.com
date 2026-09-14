<?php

declare(strict_types=1);

namespace He4rt\Events\Console\Commands;

use He4rt\Events\Closure\Jobs\ProcessEventClosureJob;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Event\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

#[Description(description: 'Dispatch closure jobs for past events with non-terminal enrollments')]
#[Signature(signature: 'events:close-pending')]
final class ClosePendingEventsCommand extends Command
{
    public function handle(): int
    {
        $count = 0;

        Event::query()
            ->where('ends_at', '<', now())
            ->whereHas('enrollments', static function (Builder $query): void {
                $query->whereIn('status', [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn]);
            })
            ->select('id')
            ->each(static function (Event $event) use (&$count): void {
                dispatch(new ProcessEventClosureJob($event->id));
                $count++;
            });

        $this->info(sprintf('Dispatched %d closure job(s).', $count));

        return self::SUCCESS;
    }
}
