<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Exceptions;

use RuntimeException;
use Throwable;

final class TwitchUnreachable extends RuntimeException
{
    public static function while(string $doing, ?Throwable $previous = null): self
    {
        return new self(sprintf('Twitch did not answer while %s.', $doing), previous: $previous);
    }
}
