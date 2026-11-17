<?php

declare(strict_types=1);

namespace Tests\Fixtures\FeatureFlags;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\InteractsWithFeatureState;
use App\Support\FeatureFlags\SharedWithFrontend;

final class MaintenanceBanner implements FeatureFlag, SharedWithFrontend
{
    use InteractsWithFeatureState;

    public function key(): string
    {
        return 'maintenance-banner';
    }

    public function label(): string
    {
        return 'Maintenance banner';
    }

    public function description(): string
    {
        return 'A banner on every page during a maintenance window.';
    }

    public function scope(): FeatureScope
    {
        return FeatureScope::Global;
    }
}
