<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class OAuthConnectionDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public IdentityProvider $provider,
        public string $providerId,
        public ClientAccessManager $credentials,
        public array $metadata,
    ) {}

    public static function fromOAuth(OAuthUserDTO $oauthUser, OAuthAccessDTO $access): self
    {
        return new self(
            provider: $oauthUser->provider,
            providerId: $oauthUser->providerId,
            credentials: $access->toClientAccessManager(),
            metadata: $oauthUser->toMetadata(),
        );
    }
}
