<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\BotDiscord\DTO\SubmissionApprovedWebhookDTO;
use Laracord\HasLaracord;

class PostSubmissionToDiscordAction
{
    use HasLaracord;

    private string $channelId;

    public function __construct()
    {
        $this->channelId = config('bot-discord.channels.100-dias-de-codigo');
    }

    public function execute(SubmissionApprovedWebhookDTO $dto): void
    {
        $this->discord()
            ->getChannel($this->channelId)
            ->sendMessage(
                "🚀 Nova submissão!\n"
                ."👤 @{$dto->userName} - [{$dto->day}/100]\n\n"
                .$dto->tweetUrl
            );
    }
}
