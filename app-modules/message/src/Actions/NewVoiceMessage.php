<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use He4rt\Character\Actions\FindCharacterIdByUserId;
use He4rt\Character\Actions\IncrementExperience;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Message\Contracts\VoiceRepository;
use He4rt\Message\DTO\NewVoiceMessageDTO;

final readonly class NewVoiceMessage
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
        private FindCharacterIdByUserId $findCharacterId,
        private IncrementExperience $characterExperience,
        private VoiceRepository $voiceRepository
    ) {}

    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $externalIdentity = $this->findExternalIdentity->handle(
            $voiceDTO->provider->value,
            $voiceDTO->providerId
        );

        $characterId = $this->findCharacterId->handle($externalIdentity->model_id);
        $obtainedExperience = $this->characterExperience->incrementByVoiceMessage(
            $characterId,
            $voiceDTO->voiceState
        );

        $this->voiceRepository->create($voiceDTO, $externalIdentity->id, $obtainedExperience);
    }
}
