<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Exceptions;

use RuntimeException;

final class SlidoSubscriptionRejected extends RuntimeException
{
    public static function withFrame(string $raw): self
    {
        return new self(sprintf('Slido rejected the subscription: %s', mb_substr($raw, 0, 200)));
    }
}
