<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\InteractsWithFeatureState;

/**
 * A user with this flag skips the sampling: every event of that user is written. See {@see TailSampler}.
 */
final class KeepEveryLog implements FeatureFlag
{
    use InteractsWithFeatureState;

    public function key(): string
    {
        return 'keep-every-log';
    }

    public function label(): string
    {
        return 'Keep every log';
    }

    public function description(): string
    {
        return 'Writes every request, job and command of this user to System > Logs, without sampling. Turn it on while you debug a problem that this user reports.';
    }

    public function scope(): FeatureScope
    {
        return FeatureScope::User;
    }
}
