<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Exceptions;

use LogicException;

final class DuplicateFeatureKey extends LogicException
{
    public static function of(string $key): self
    {
        return new self(sprintf('Two feature flags use the key "%s".', $key));
    }
}
