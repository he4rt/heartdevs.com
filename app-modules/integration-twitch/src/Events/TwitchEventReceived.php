<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Events;

use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class TwitchEventReceived
{
    use Dispatchable;

    public function __construct(
        public TwitchEventLog $eventLog,
    ) {}
}
