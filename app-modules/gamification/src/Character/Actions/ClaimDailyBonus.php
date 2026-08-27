<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class ClaimDailyBonus
{
    public function __construct(
        private PersistDailyBonus $dailyBonus,
        private FindExternalIdentity $findExternalIdentity,
    ) {}

    public function handle(string $provider, string $providerId): void
    {
        $externalIdentity = $this->findExternalIdentity->handle($provider, $providerId);
        $characterId = Character::query()->where('user_id', $externalIdentity->model_id)->value('id');

        $this->dailyBonus->handle($characterId);
    }
}
