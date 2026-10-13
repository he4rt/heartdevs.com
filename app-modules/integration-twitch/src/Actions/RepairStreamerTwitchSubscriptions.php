<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class RepairStreamerTwitchSubscriptions
{
    public function __construct(
        private ReconcileStreamerTwitchSubscriptions $reconcile,
        private SyncStreamerTwitchSubscriptions $sync,
    ) {}

    /**
     * @throws TwitchUnreachable
     */
    public function handle(StreamerSource $source): void
    {
        $this->reconcile->handle($source);
        $this->sync->handle($source);
    }
}
