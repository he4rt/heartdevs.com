<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Badge\Actions\FindBadgeBySlug;
use He4rt\Provider\Actions\FindProvider;

final readonly class ClaimCharacterBadge
{
    public function __construct(
        private PersistClaimedBadge $claimBadge,
        private FindProvider $findProvider,
        private FindCharacterIdByUserId $findCharacter,
        private FindBadgeBySlug $findBadgeBySlug,
    ) {}

    public function handle(string $provider, string $providerId, string $badgeSlug): void
    {
        $providerEntity = $this->findProvider->handle($provider, $providerId);
        $characterId = $this->findCharacter->handle($providerEntity->modelId);
        $badgeEntity = $this->findBadgeBySlug->handle($badgeSlug);

        $this->claimBadge->handle($characterId, $badgeEntity->id);
    }
}
