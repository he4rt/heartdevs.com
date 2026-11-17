<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

/**
 * Reads the flag that uses it through {@see FeatureState}: `NewCheckout::activeFor($team)`, `MaintenanceBanner::active()`.
 *
 * @phpstan-require-implements FeatureFlag
 */
trait InteractsWithFeatureState
{
    public static function gateIsOpen(): bool
    {
        return FeatureState::gateIsOpen(new self);
    }

    public static function active(): bool
    {
        return FeatureState::isActive(new self);
    }

    public static function activeFor(Team|User $scope): bool
    {
        return FeatureState::isActive(new self, $scope);
    }
}
