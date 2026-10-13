<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class MergeConflictDTO
{
    public function __construct(
        public string $conflictingUserId,
        public IdentityProvider $provider,
        public OAuthAccessDTO $credentials,
        public OAuthUserDTO $oauthUser,
    ) {}

    public function toPending(): PendingOAuthMergeDTO
    {
        return new PendingOAuthMergeDTO(
            conflictingUserId: $this->conflictingUserId,
            connection: OAuthConnectionDTO::fromOAuth($this->oauthUser, $this->credentials),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSession(): array
    {
        return $this->toPending()->toSession();
    }
}
