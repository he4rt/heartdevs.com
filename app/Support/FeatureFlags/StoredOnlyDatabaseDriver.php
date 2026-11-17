<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use Laravel\Pennant\Drivers\DatabaseDriver;

/**
 * Pennant's database driver stores the resolved value on the first read of every scope.
 * This driver answers false for a missing row and stores nothing, so only a value that a super admin set is ever kept.
 */
final class StoredOnlyDatabaseDriver extends DatabaseDriver
{
    protected function resolveValue($feature, $scope): mixed
    {
        return $this->unknownFeatureValue;
    }
}
