<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Actions;

use App\Support\Activitylog\AuditActivity;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\StoredFeatureFlag;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

final readonly class OpenFeatureGate
{
    public function handle(FeatureFlag $flag): void
    {
        $isOpen = Feature::for(scope: null)->active($flag->key());

        if ($isOpen) {
            return;
        }

        DB::transaction(function () use ($flag): void {
            Feature::for(scope: null)->activate($flag->key());

            AuditActivity::event(StoredFeatureFlag::gateOf($flag), 'feature_flag.gate_opened', ['feature' => $flag->key()], sprintf('%s gate opened', $flag->label()));
        });
    }
}
