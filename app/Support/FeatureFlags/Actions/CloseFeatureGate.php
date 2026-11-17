<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Actions;

use App\Support\Activitylog\AuditActivity;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\StoredFeatureFlag;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

/**
 * Turns the flag off for everyone. The teams and users it was on for keep their rows, so opening the gate brings them back.
 */
final readonly class CloseFeatureGate
{
    public function handle(FeatureFlag $flag): void
    {
        $isOpen = Feature::for(scope: null)->active($flag->key());

        if (!$isOpen) {
            return;
        }

        DB::transaction(function () use ($flag): void {
            Feature::for(scope: null)->deactivate($flag->key());

            AuditActivity::event(StoredFeatureFlag::gateOf($flag), 'feature_flag.gate_closed', ['feature' => $flag->key()], sprintf('%s gate closed', $flag->label()));
        });
    }
}
