<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Provider\Actions\FindProvider;

final readonly class ClaimDailyBonus
{
    public function __construct(
        private PersistDailyBonus $dailyBonus,
        private FindProvider $findProvider,
        private FindCharacterIdByUserId $findCharacter,
    ) {}

    public function handle(string $provider, string $providerId): void
    {
        $providerEntity = $this->findProvider->handle($provider, $providerId);

        $characterId = $this->findCharacter->handle($providerEntity->modelId);

        $this->dailyBonus->handle($characterId);
    }
}
