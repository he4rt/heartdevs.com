<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use He4rt\BotDiscord\Actions\VoiceChannel\DeleteVoiceChannelAction;
use Laracord\Tasks\Task;

class DynamicVoiceTask extends Task
{
    /**
     * The task interval.
     */
    protected int $interval = 30;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Handle the task.
     */
    public function handle(): void
    {
        resolve(DeleteVoiceChannelAction::class)->execute($this->discord());
    }
}
