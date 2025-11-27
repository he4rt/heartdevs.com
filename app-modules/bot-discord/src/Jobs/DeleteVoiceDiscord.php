<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Jobs;

use Discord\Discord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteVoiceDiscord implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $channelId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Discord $discord): void
    {
        $discord->getChannel($this->channelId)->delete();
    }
}
