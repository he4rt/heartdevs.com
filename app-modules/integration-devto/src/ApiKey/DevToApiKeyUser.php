<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\ApiKey;

use He4rt\Identity\ExternalIdentity\DTOs\ApiKeyUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class DevToApiKeyUser extends ApiKeyUserDTO
{
    public static function make(array $payload): self
    {
        return new self(
            providerId: (string) $payload['id'],
            provider: IdentityProvider::DevTo,
            username: $payload['username'],
            name: $payload['name'] ?? $payload['username'],
            email: $payload['email'] ?? null,
            avatarUrl: $payload['profile_image'] ?? null,
        );
    }
}
