<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Actions;

use App\Support\Activitylog\AuditActivity;
use App\Support\FeatureFlags\FeatureFlag;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

/**
 * Removes the stored row: a flag is off wherever nothing is stored.
 */
final readonly class DisableFeatureFlag
{
    public function handle(FeatureFlag $flag, Team|User $scope): void
    {
        $isEnabled = Feature::for($scope)->active($flag->key());

        if (!$isEnabled) {
            return;
        }

        DB::transaction(function () use ($flag, $scope): void {
            Feature::for($scope)->forget($flag->key());

            AuditActivity::event($scope, 'feature_flag.disabled', ['feature' => $flag->key()], sprintf('%s disabled', $flag->label()));
        });
    }
}
