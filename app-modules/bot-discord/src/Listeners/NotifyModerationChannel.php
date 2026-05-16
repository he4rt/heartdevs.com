<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Listeners;

use He4rt\BotDiscord\Moderation\ModerationEmbedBuilder;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\CreateMessage;
use He4rt\Moderation\Cases\Events\CaseQueued;
use Illuminate\Support\Facades\Log;

/**
 * Sends an embed notification to the moderation channel when a new case is queued for review.
 * Mentions admin and mod roles so the team is alerted immediately.
 */
final readonly class NotifyModerationChannel
{
    public function __construct(
        private DiscordConnector $connector,
        private ModerationEmbedBuilder $embedBuilder,
    ) {}

    public function handle(CaseQueued $event): void
    {
        $channelId = config()->string('he4rt.discord.moderation.mod_channel_id');

        if (blank($channelId)) {
            return;
        }

        $case = $event->case;

        $response = $this->connector->send(new CreateMessage($channelId, [
            'content' => $this->embedBuilder->buildRoleMentions(),
            'embeds' => [$this->embedBuilder->buildCaseEmbed($case)],
        ]));

        if ($response->failed()) {
            Log::warning('Failed to notify mod channel about new moderation case.', [
                'case_id' => $case->id,
                'status' => $response->status(),
            ]);
        }
    }
}
