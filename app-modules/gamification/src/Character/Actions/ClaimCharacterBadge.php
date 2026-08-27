<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Actions;

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

final readonly class ClaimCharacterBadge
{
    public function __construct(
        private PersistClaimedBadge $claimBadge,
        private FindExternalIdentity $findExternalIdentity,
    ) {}

    public function handle(string $provider, string $providerId, string $badgeSlug): void
    {
        $externalIdentity = $this->findExternalIdentity->handle($provider, $providerId);
        $characterId = Character::query()->where('user_id', $externalIdentity->model_id)->value('id');
        $badge = Badge::query()->where('redeem_code', $badgeSlug)->firstOrFail();

        $this->claimBadge->handle($characterId, $badge->id);
    }
}
