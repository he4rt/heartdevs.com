<?php

declare(strict_types=1);

namespace He4rt\Message\Repositories;

use He4rt\Message\Contracts\VoiceRepository;
use He4rt\Message\DTO\NewVoiceMessageDTO;
use He4rt\Message\Entities\VoiceEntity;
use He4rt\Message\Models\Voice;

final readonly class VoiceEloquentRepository implements VoiceRepository
{
    public function __construct(private Voice $model) {}

    public function create(NewVoiceMessageDTO $messageDTO, string $providerId, int $obtainedExperience): VoiceEntity
    {
        $model = $this->model->newQuery()->create([
            'tenant_id' => request()->input('tenant_id'),
            'provider_id' => $providerId,
            'channel_name' => $messageDTO->channelName,
            'state' => $messageDTO->voiceState->value,
            'obtained_experience' => $obtainedExperience,
        ]);

        return VoiceEntity::make($model->toArray());
    }
}
