<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class ClaimDailyBonus
{
    public function __construct(
        private PersistDailyBonus $dailyBonus,
        private FindExternalIdentity $findExternalIdentity,
        private FindCharacterIdByUserId $findCharacter,
    ) {}

    public function handle(string $provider, string $providerId): void
    {
        $externalIdentity = $this->findExternalIdentity->handle($provider, $providerId);

        $characterId = $this->findCharacter->handle($externalIdentity->model_id);

        $this->dailyBonus->handle($characterId);
    }
}
