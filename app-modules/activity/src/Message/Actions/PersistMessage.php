<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Actions;

use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;

class PersistMessage
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        NewMessageDTO $messageDTO,
        int $obtainedExperience,
        string $providerEntity,
        array $metadata = [],
    ): Message {
        return Message::query()->createOrFirst(
            ['provider_message_id' => $messageDTO->providerMessageId],
            [
                'platform' => $messageDTO->provider,
                'external_identity_id' => $providerEntity,
                'channel_id' => $messageDTO->channelId,
                'content' => $messageDTO->content,
                'sent_at' => $messageDTO->sentAt,
                'obtained_experience' => $obtainedExperience,
                'metadata' => $metadata === [] ? null : $metadata,
            ],
        );
    }
}
