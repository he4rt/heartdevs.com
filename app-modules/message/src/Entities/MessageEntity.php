<?php

declare(strict_types=1);

namespace He4rt\Message\Entities;

use DateTimeImmutable;

class MessageEntity
{
    public function __construct(
        public string $id,
        public string $providerId,
        public string $providerMessageId,
        public string $channelId,
        public string $content,
        public DateTimeImmutable $sentAt,
        public int $obtainedExperience,
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            providerId: $payload['provider_id'],
            providerMessageId: $payload['provider_message_id'],
            channelId: $payload['channel_id'],
            content: $payload['content'],
            sentAt: $payload['sent_at'],
            obtainedExperience: $payload['obtained_experience']
        );
    }
}
