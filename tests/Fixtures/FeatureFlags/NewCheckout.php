<?php

declare(strict_types=1);

namespace Tests\Fixtures\FeatureFlags;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\InteractsWithFeatureState;

final class NewCheckout implements FeatureFlag
{
    use InteractsWithFeatureState;

    public function key(): string
    {
        return 'new-checkout';
    }

    public function label(): string
    {
        return 'New checkout';
    }

    public function description(): string
    {
        return 'The redesigned checkout page.';
    }

    public function scope(): FeatureScope
    {
        return FeatureScope::Team;
    }
}
