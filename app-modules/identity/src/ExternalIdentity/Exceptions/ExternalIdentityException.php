<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class ExternalIdentityException extends Exception
{
    public static function notFound(string $provider, string $providerId): self
    {
        $message = sprintf(
            "Provider %s has no candidate for ID '%s'",
            $provider,
            $providerId
        );

        return new self($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
