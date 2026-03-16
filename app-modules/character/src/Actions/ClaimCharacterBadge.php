<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Badge\Actions\FindBadgeBySlug;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class ClaimCharacterBadge
{
    public function __construct(
        private PersistClaimedBadge $claimBadge,
        private FindExternalIdentity $findExternalIdentity,
        private FindCharacterIdByUserId $findCharacter,
        private FindBadgeBySlug $findBadgeBySlug,
    ) {}

    public function handle(string $provider, string $providerId, string $badgeSlug): void
    {
        $externalIdentity = $this->findExternalIdentity->handle($provider, $providerId);
        $characterId = $this->findCharacter->handle($externalIdentity->model_id);
        $badgeEntity = $this->findBadgeBySlug->handle($badgeSlug);

        $this->claimBadge->handle($characterId, $badgeEntity->id);
    }
}
