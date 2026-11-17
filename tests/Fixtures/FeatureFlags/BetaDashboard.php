<?php

declare(strict_types=1);

namespace Tests\Fixtures\FeatureFlags;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\InteractsWithFeatureState;
use App\Support\FeatureFlags\SharedWithFrontend;

final class BetaDashboard implements FeatureFlag, SharedWithFrontend
{
    use InteractsWithFeatureState;

    public function key(): string
    {
        return 'beta-dashboard';
    }

    public function label(): string
    {
        return 'Beta dashboard';
    }

    public function description(): string
    {
        return 'The new home with live charts.';
    }

    public function scope(): FeatureScope
    {
        return FeatureScope::User;
    }
}
