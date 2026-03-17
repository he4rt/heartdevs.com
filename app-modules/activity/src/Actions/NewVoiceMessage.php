<?php

declare(strict_types=1);

namespace He4rt\Activity\Actions;

use He4rt\Activity\DTOs\NewVoiceMessageDTO;
use He4rt\Activity\Models\Voice;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class NewVoiceMessage
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
        private IncrementExperience $characterExperience,
    ) {}

    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $externalIdentity = $this->findExternalIdentity->handle(
            $voiceDTO->provider->value,
            $voiceDTO->providerId
        );

        $characterId = Character::query()
            ->where('tenant_id', request()->tenant_id)
            ->where('user_id', $externalIdentity->model_id)
            ->value('id');

        $obtainedExperience = $this->characterExperience->incrementByVoiceMessage(
            $characterId,
            $voiceDTO->voiceState
        );

        Voice::query()->create([
            'tenant_id' => request()->tenant_id,
            'provider_id' => $externalIdentity->id,
            'channel_name' => $voiceDTO->channelName,
            'state' => $voiceDTO->voiceState->value,
            'obtained_experience' => $obtainedExperience,
        ]);
    }
}
