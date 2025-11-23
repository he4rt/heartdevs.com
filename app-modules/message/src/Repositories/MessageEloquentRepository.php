<?php

declare(strict_types=1);

namespace He4rt\Message\Repositories;

use He4rt\Message\Contracts\MessageRepository;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Message\Entities\MessageEntity;
use He4rt\Message\Models\Message;
use Illuminate\Database\Eloquent\Builder;

final readonly class MessageEloquentRepository implements MessageRepository
{
    private Builder $query;

    public function __construct(private Message $model)
    {
        $this->query = $this->model->newQuery();
    }

    public function create(NewMessageDTO $messageDTO, string $providerId, int $obtainedExperience): MessageEntity
    {
        $model = $this->query->create([
            'tenant_id' => $messageDTO->tenantId,
            'provider_id' => $providerId,
            'provider_message_id' => $messageDTO->providerMessageId,
            'channel_id' => $messageDTO->channelId,
            'content' => $messageDTO->content,
            'sent_at' => $messageDTO->sentAt,
            'obtained_experience' => $obtainedExperience,
        ]);

        return MessageEntity::make($model->toArray());
    }
}
