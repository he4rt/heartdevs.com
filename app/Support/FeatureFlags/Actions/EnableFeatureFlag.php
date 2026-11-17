<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Actions;

use App\Support\Activitylog\AuditActivity;
use App\Support\FeatureFlags\FeatureFlag;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

final readonly class EnableFeatureFlag
{
    public function handle(FeatureFlag $flag, Team|User $scope): void
    {
        $isEnabled = Feature::for($scope)->active($flag->key());

        if ($isEnabled) {
            return;
        }

        DB::transaction(function () use ($flag, $scope): void {
            Feature::for($scope)->activate($flag->key());

            AuditActivity::event($scope, 'feature_flag.enabled', ['feature' => $flag->key()], sprintf('%s enabled', $flag->label()));
        });
    }
}
