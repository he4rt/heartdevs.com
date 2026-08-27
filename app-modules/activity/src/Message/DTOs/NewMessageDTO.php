<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\DTOs;

use DateTimeImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class NewMessageDTO
{
    public function __construct(
        public IdentityProvider $provider,
        public string $providerUsername,
        public string $externalAccountId,
        public string $providerMessageId,
        public string $channelId,
        public string $content,
        public DateTimeImmutable $sentAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            provider: IdentityProvider::from($payload['provider']),
            providerUsername: $payload['provider_username'],
            externalAccountId: $payload['external_account_id'],
            providerMessageId: $payload['provider_message_id'],
            channelId: $payload['channel_id'],
            content: $payload['content'],
            sentAt: new DateTimeImmutable($payload['sent_at'])
        );
    }
}
