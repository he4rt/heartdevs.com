<?php

declare(strict_types=1);

namespace App\Support\Logging;

use He4rt\Identity\Users\User;
use Illuminate\Support\Lottery;

/**
 * Decides at the end of a unit if its event is written. The first rule that matches is the reason.
 * The {@see KeepEveryLog} flag costs a query, so it runs only when no rule before it matches.
 */
final readonly class TailSampler
{
    public function keepReason(WideEvent $event): ?string
    {
        $user = $event->user();
        $kind = $event->kind();

        return match (true) {
            $event->failed() => 'error',
            $event->durationMs() > config()->integer('logging.wide_events.slow_ms') => 'slow',
            $kind instanceof WideEventKind && $kind->alwaysKept() => $kind->value,
            $event->readAnActiveScopedFlag() => 'feature_flag',
            $user instanceof User && KeepEveryLog::activeFor($user) => 'user',
            Lottery::odds(config()->float('logging.wide_events.sample_rate'))->choose() === true => 'sampled',
            default => null,
        };
    }
}
