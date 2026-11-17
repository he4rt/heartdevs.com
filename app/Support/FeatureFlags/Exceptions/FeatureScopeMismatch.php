<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Exceptions;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use LogicException;

final class FeatureScopeMismatch extends LogicException
{
    public static function of(FeatureFlag $flag, FeatureScope $asked): self
    {
        return new self(sprintf(
            'The feature flag "%s" is switched per %s, not per %s.',
            $flag->key(),
            $flag->scope()->value,
            $asked->value,
        ));
    }
}
