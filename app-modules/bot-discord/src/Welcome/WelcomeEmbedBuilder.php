<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Welcome;

use He4rt\BotDiscord\DTO\WelcomeContextDTO;
use Laracord\Discord\Message;

/**
 * Builds the welcome embed. The DM and the public fallback share the same
 * layout, so the message is decorated once and re-sent with another description.
 */
final class WelcomeEmbedBuilder
{
    public function decorate(Message $message, WelcomeContextDTO $context, string $generalChannelId): Message
    {
        return $message
            ->title(__('bot-discord::welcome.embed.title'))
            ->content(__('bot-discord::welcome.dm.description', ['username' => $context->username]))
            ->thumbnailUrl($context->serverIconUrl)
            ->field(
                __('bot-discord::welcome.embed.cta_title'),
                __('bot-discord::welcome.embed.cta'),
                inline: false
            )
            ->button(__('bot-discord::welcome.buttons.present'), $this->deepLink($context->guildId, $generalChannelId), '✍️')
            ->button(__('bot-discord::welcome.buttons.portal'), 'https://heartdevs.com', '🌐')
            ->button(__('bot-discord::welcome.buttons.socials'), 'https://heartdevs.com/redes', '🔗')
            ->footerText(__('bot-discord::welcome.embed.footer', ['year' => now()->format('Y')]))
            ->timestamp(now())
            ->color('#782bf1');
    }

    /**
     * Swap the decorated message into its public form: the fallback text inside
     * the embed and a pinging mention above it.
     */
    public function asFallback(Message $message, WelcomeContextDTO $context): Message
    {
        return $message
            ->content(__('bot-discord::welcome.fallback.description'))
            ->body(__('bot-discord::welcome.fallback.body', ['mention' => $context->mention()]));
    }

    private function deepLink(string $guildId, string $channelId): string
    {
        return sprintf('https://discord.com/channels/%s/%s', $guildId, $channelId);
    }
}
