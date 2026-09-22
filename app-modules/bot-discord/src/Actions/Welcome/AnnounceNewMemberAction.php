<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\Welcome;

use He4rt\BotDiscord\DTO\WelcomeContextDTO;
use Laracord\Discord\Message;
use Laracord\HasLaracord;

/**
 * @method Message message(string $content = '')
 */
final class AnnounceNewMemberAction
{
    use HasLaracord;

    private readonly string $autoReportChannelId;

    public function __construct()
    {
        $this->autoReportChannelId = config()->string('bot-discord.channels.auto-report');
    }

    public function execute(WelcomeContextDTO $context): void
    {
        $this
            ->message(__('bot-discord::welcome.announcement.message', ['username' => $context->username]))
            ->title(__('bot-discord::welcome.announcement.title'))
            ->thumbnailUrl($context->avatarUrl)
            ->body(__('bot-discord::welcome.announcement.body', ['mention' => $context->mention()]))
            ->color('#5865F2')
            ->send($this->autoReportChannelId);
    }

    /**
     * Announce a member whose internal profile could not be initialized, so the
     * moderation team knows the welcome flow degraded.
     */
    public function executeProfileFailure(WelcomeContextDTO $context): void
    {
        $this
            ->message(__('bot-discord::welcome.profile_failure.message'))
            ->title(__('bot-discord::welcome.profile_failure.title'))
            ->body(__('bot-discord::welcome.profile_failure.body', ['mention' => $context->mention()]))
            ->color('#ED4245')
            ->send($this->autoReportChannelId);
    }
}
