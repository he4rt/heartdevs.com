<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Exceptions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use RuntimeException;

final class OAuthFlowException extends RuntimeException
{
    public static function providerNotSupported(string $provider): self
    {
        return new self(sprintf('OAuth provider "%s" is not supported.', $provider));
    }

    public static function clientNotConfigured(IdentityProvider $provider): self
    {
        return new self(sprintf('OAuth client for "%s" is not configured.', $provider->value));
    }

    public static function unauthenticatedLinkAttempt(): self
    {
        return new self('Cannot link a provider without an authenticated user.');
    }

    public static function tenantNotFound(string $identifier): self
    {
        return new self(sprintf('Tenant "%s" not found.', $identifier));
    }
}
