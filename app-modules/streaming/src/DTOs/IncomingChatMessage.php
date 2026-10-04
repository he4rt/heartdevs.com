<?php

declare(strict_types=1);

namespace He4rt\Streaming\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;

final readonly class IncomingChatMessage
{
    public function __construct(
        public IdentityProvider $platform,
        public string $broadcasterId,
        public string $providerMessageId,
        public string $chatterId,
        public string $chatterLogin,
        public string $content,
        public CarbonImmutable $sentAt,
        public ChatMessageMetadata $metadata,
    ) {}
}
