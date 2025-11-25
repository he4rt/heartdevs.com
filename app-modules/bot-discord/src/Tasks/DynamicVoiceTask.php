<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Laracord\Tasks\Task;

class DynamicVoiceTask extends Task
{
    /**
     * The task interval.
     */
    protected int $interval = 5;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Handle the task.
     */
    public function handle(): void
    {
        $this->logger()->info('Hello world.');
    }
}
