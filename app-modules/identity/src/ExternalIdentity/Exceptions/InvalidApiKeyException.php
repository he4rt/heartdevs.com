<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Exceptions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use RuntimeException;

final class InvalidApiKeyException extends RuntimeException
{
    public function __construct(
        public readonly IdentityProvider $provider,
        public readonly int $status,
    ) {
        parent::__construct(sprintf(
            'Invalid API key for %s (HTTP %d).',
            $provider->getLabel(),
            $status,
        ));
    }
}
