<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use He4rt\Character\Actions\FindCharacterIdByUserId;
use He4rt\Character\Actions\IncrementExperience;
use He4rt\Message\Contracts\VoiceRepository;
use He4rt\Message\DTO\NewVoiceMessageDTO;
use He4rt\Provider\Actions\FindProvider;

final readonly class NewVoiceMessage
{
    public function __construct(
        private FindProvider $findProvider,
        private FindCharacterIdByUserId $findCharacterId,
        private IncrementExperience $characterExperience,
        private VoiceRepository $voiceRepository
    ) {}

    public function persist(array $payload): void
    {
        $voiceDTO = NewVoiceMessageDTO::make($payload);
        $provider = $this->findProvider->handle(
            $voiceDTO->provider->value,
            $voiceDTO->providerId
        );

        $characterId = $this->findCharacterId->handle($provider->modelId);
        $obtainedExperience = $this->characterExperience->incrementByVoiceMessage(
            $characterId,
            $voiceDTO->voiceState
        );

        $this->voiceRepository->create($voiceDTO, $provider->id, $obtainedExperience);
    }
}
