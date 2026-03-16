<?php

declare(strict_types=1);

namespace He4rt\Message\DTO;

use DateTimeImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class NewMessageDTO
{
    public function __construct(
        public int $tenantId,
        public IdentityProvider $provider,
        public string $providerUsername,
        public string $providerId,
        public string $providerMessageId,
        public string $channelId,
        public string $content,
        public DateTimeImmutable $sentAt,
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            tenantId: $payload['tenant_id'],
            provider: IdentityProvider::from($payload['provider']),
            providerUsername: $payload['provider_username'],
            providerId: $payload['provider_id'],
            providerMessageId: $payload['provider_message_id'],
            channelId: $payload['channel_id'],
            content: $payload['content'],
            sentAt: new DateTimeImmutable($payload['sent_at'])
        );
    }
}
