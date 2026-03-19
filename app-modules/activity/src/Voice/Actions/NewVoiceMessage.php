<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Actions;

use He4rt\Activity\Voice\DTOs\NewVoiceMessageDTO;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class NewVoiceMessage
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
        private IncrementExperience $characterExperience,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $externalIdentity = $this->findExternalIdentity->handle(
            $voiceDTO->provider->value,
            $voiceDTO->externalAccountId
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
            'external_identity_id' => $externalIdentity->id,
            'channel_name' => $voiceDTO->channelName,
            'state' => $voiceDTO->voiceState->value,
            'obtained_experience' => $obtainedExperience,
        ]);
    }
}
