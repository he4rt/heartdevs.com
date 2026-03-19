<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Actions;

use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;

class PersistMessage
{
    public function handle(
        NewMessageDTO $messageDTO,
        int $obtainedExperience,
        string $providerEntity
    ): Message {
        return Message::query()->create([
            'tenant_id' => $messageDTO->tenantId,
            'external_identity_id' => $providerEntity,
            'provider_message_id' => $messageDTO->providerMessageId,
            'channel_id' => $messageDTO->channelId,
            'content' => $messageDTO->content,
            'sent_at' => $messageDTO->sentAt,
            'obtained_experience' => $obtainedExperience,
        ]);
    }
}
