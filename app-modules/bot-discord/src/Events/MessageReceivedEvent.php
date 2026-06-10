<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use He4rt\Activity\Message\Actions\NewMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\BotDiscord\Actions\ResolveDiscordTenant;
use He4rt\BotDiscord\Moderation\DiscordModerationAdapter;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Pipeline\SubmitForModeration;
use Laracord\Events\Event;
use Throwable;

/**
 * Thin event handler for Discord MESSAGE_CREATE.
 *
 * Responsibilities: resolve tenant, track activity, submit to moderation pipeline.
 * All classification, routing, and enforcement logic lives in the moderation module.
 */
class MessageReceivedEvent extends Event
{
    protected $handler = Events::MESSAGE_CREATE;

    public function handle(Message $message): void
    {
        if ($message->author->bot) {
            return;
        }

        try {
            /**
             * Dispatches the raw message payload to the ingestion queue for
             * asynchronous processing (activity tracking, telemetry, moderation).
             */
            event('discord.message.received', [
                'raw_payload' => $message->getRawAttributes(),
            ]);

            $tenantProvider = resolve(ResolveDiscordTenant::class)->handle((string) $message->guild_id);

            // Activity tracking — records message for XP/gamification regardless of moderation outcome.
            resolve(NewMessage::class)->persist(new NewMessageDTO(
                tenantId: $tenantProvider->tenant_id,
                provider: IdentityProvider::Discord,
                providerUsername: $message->author->username.'#'.$message->author->discriminator,
                externalAccountId: $message->user_id,
                providerMessageId: $message->id,
                channelId: $message->channel_id,
                content: $message->content,
                sentAt: $message->timestamp->toDateTimeImmutable()
            ));

            // Moderation pipeline — SubmitForModeration handles pre-screen (sync) + async AI.
            // See ADR-0001 for architecture details.
            $content = DiscordModerationAdapter::make()->ingest([
                'message_id' => $message->id,
                'author_id' => $message->user_id,
                'content' => $message->content,
                'channel_id' => $message->channel_id,
                'guild_id' => (string) $message->guild_id,
                'username' => $message->author->username,
                'attachments' => [],
                'tenant_id' => (string) $tenantProvider->tenant_id,
            ]);

            resolve(SubmitForModeration::class)->execute($content, CaseSource::AutoDetect);

        } catch (Throwable $throwable) {
            $this->logger()->error(
                sprintf('%s | File: %s | Line: %s', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine()),
            );
        }
    }
}
