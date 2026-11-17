<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Queue\Events\JobAttempted;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Context;

/**
 * Opens and writes the wide event of a queued job, an Artisan command and a scheduled closure.
 *
 * Two framework details shape it:
 * - The worker fires JobAttempted before it reports the exception, so the job event takes the exception
 *   from JobAttempted, and {@see WideEvent::recordError()} skips the report that follows.
 * - The scheduler sends the Context to the process of a scheduled command, so that command writes one
 *   `command` event with `schedule.expression`. Only a scheduled closure writes a `schedule` event.
 */
final readonly class RecordQueueAndConsoleEvents
{
    private const string SCHEDULE_EXPRESSION = 'schedule.expression';

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            JobProcessing::class => 'jobStarted',
            JobAttempted::class => 'jobAttempted',
            CommandStarting::class => 'commandStarted',
            CommandFinished::class => 'commandFinished',
            ScheduledTaskStarting::class => 'taskStarted',
            ScheduledTaskFinished::class => 'taskFinished',
            ScheduledTaskFailed::class => 'taskFailed',
        ];
    }

    public function jobStarted(JobProcessing $processing): void
    {
        if ($processing->job instanceof SyncJob || !$this->wideEvent()->start(WideEventKind::Job)) {
            return;
        }

        $this->wideEvent()->set('job.name', $processing->job->resolveName());
        $this->wideEvent()->set('job.queue', $processing->job->getQueue());
        $this->wideEvent()->set('job.connection', $processing->connectionName);
        $this->wideEvent()->set('job.attempt', $processing->job->attempts());
    }

    public function jobAttempted(JobAttempted $attempted): void
    {
        if ($this->wideEvent()->kind() !== WideEventKind::Job) {
            return;
        }

        if ($attempted->exception !== null) {
            $this->wideEvent()->recordError($attempted->exception);
        }

        $this->wideEvent()->set('job.released', $attempted->job->isReleased());
        $this->wideEvent()->set('job.failed', $attempted->job->hasFailed());
        $this->wideEvent()->finish();
    }

    public function commandStarted(CommandStarting $starting): void
    {
        if ($this->isIgnored($starting->command) || !$this->wideEvent()->start(WideEventKind::Command)) {
            return;
        }

        $this->wideEvent()->set('command.name', $starting->command);

        $scheduleExpression = Context::getHidden(self::SCHEDULE_EXPRESSION);

        if (is_string($scheduleExpression)) {
            $this->wideEvent()->set(self::SCHEDULE_EXPRESSION, $scheduleExpression);
        }
    }

    public function commandFinished(CommandFinished $finished): void
    {
        $isTheOpenCommand = $this->wideEvent()->kind() === WideEventKind::Command
            && ($this->wideEvent()->fields()['command.name'] ?? null) === $finished->command;

        if (!$isTheOpenCommand) {
            return;
        }

        $this->wideEvent()->set('command.exit_code', $finished->exitCode);
        $this->wideEvent()->finish();
    }

    public function taskStarted(ScheduledTaskStarting $starting): void
    {
        RequestId::begin(request());
        Context::addHidden(self::SCHEDULE_EXPRESSION, $starting->task->expression);

        if (!$starting->task instanceof CallbackEvent || !$this->wideEvent()->start(WideEventKind::Schedule)) {
            return;
        }

        $this->wideEvent()->set('schedule.task', $starting->task->getSummaryForDisplay());
        $this->wideEvent()->set(self::SCHEDULE_EXPRESSION, $starting->task->expression);
    }

    public function taskFinished(): void
    {
        Context::forgetHidden(self::SCHEDULE_EXPRESSION);
        if ($this->wideEvent()->kind() === WideEventKind::Schedule) {
            $this->wideEvent()->finish();
        }
    }

    public function taskFailed(ScheduledTaskFailed $failed): void
    {
        Context::forgetHidden(self::SCHEDULE_EXPRESSION);

        if ($this->wideEvent()->kind() === WideEventKind::Schedule) {
            $this->wideEvent()->recordError($failed->exception);
            $this->wideEvent()->finish();
        }
    }

    private function isIgnored(string $command): bool
    {
        return in_array($command, config()->array('logging.wide_events.ignore_commands'), strict: true);
    }

    /**
     * The worker replaces the scoped event before every job, so the event is resolved on each call.
     */
    private function wideEvent(): WideEvent
    {
        return resolve(WideEvent::class);
    }
}
