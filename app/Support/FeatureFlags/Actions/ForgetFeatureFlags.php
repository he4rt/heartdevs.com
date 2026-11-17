<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Actions;

use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Laravel\Pennant\Feature;

/**
 * Removes every flag value stored for a team or a user that is about to be deleted.
 */
final readonly class ForgetFeatureFlags
{
    public function handle(Team|User $scope): void
    {
        StoredFeatureFlag::query()->storedFor($scope)->delete();

        Feature::flushCache();
    }
}
