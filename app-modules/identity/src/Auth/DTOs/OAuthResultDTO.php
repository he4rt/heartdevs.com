<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

final readonly class OAuthResultDTO
{
    public function __construct(
        public User $user,
        public Tenant $tenant,
        public ?ExternalIdentity $identity,
        public OAuthIntent $intent,
        public string $redirectUrl,
        public ?MergeConflictDTO $mergeConflict = null,
    ) {}

    public function hasMergeConflict(): bool
    {
        return $this->mergeConflict instanceof MergeConflictDTO;
    }
}
