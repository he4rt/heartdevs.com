<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Support\FeatureFlags\Exceptions\FeatureScopeMismatch;
use App\Support\Logging\WideEvent;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Laravel\Pennant\Feature;

/**
 * A flag is on when its gate is open and, for a team or user flag, when it is also on for that scope.
 * A flag never replaces a permission. The caller still checks the RBAC ability.
 */
final class FeatureState
{
    public static function gateIsOpen(FeatureFlag $flag): bool
    {
        return Feature::for(scope: null)->active($flag->key());
    }

    /**
     * @throws FeatureScopeMismatch when the scope is not the one the flag is switched by
     */
    public static function isActive(FeatureFlag $flag, Team|User|null $scope = null): bool
    {
        $asked = $scope === null ? FeatureScope::Global : FeatureScope::of($scope);

        if ($flag->scope() !== $asked) {
            throw FeatureScopeMismatch::of($flag, $asked);
        }

        $isActive = self::gateIsOpen($flag) && ($scope === null || Feature::for($scope)->active($flag->key()));
        resolve(WideEvent::class)->recordFlag($flag, $isActive);

        return $isActive;
    }
}
