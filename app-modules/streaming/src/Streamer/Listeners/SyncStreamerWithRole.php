<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Listeners;

use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Streamer\Actions\ActivateStreamer;
use He4rt\Streaming\Streamer\Actions\DisableStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

final readonly class SyncStreamerWithRole implements ShouldQueueAfterCommit
{
    public function __construct(
        private ActivateStreamer $activateStreamer,
        private DisableStreamer $disableStreamer,
    ) {}

    public function handle(RoleAttachedEvent|RoleDetachedEvent $event): void
    {
        $user = $event->model;

        if (!$user instanceof User) {
            return;
        }

        $streamer = Streamer::query()->whereBelongsTo($user)->first();

        if (!$streamer instanceof Streamer) {
            return;
        }

        $user->can('use-streamer-tools')
            ? $this->activateStreamer->handle($streamer)
            : $this->disableStreamer->handle($streamer);
    }
}
