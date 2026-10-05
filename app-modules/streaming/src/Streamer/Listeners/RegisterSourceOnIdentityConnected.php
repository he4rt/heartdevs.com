<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Listeners;

use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Streamer\Actions\RegisterStreamerSource;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class RegisterSourceOnIdentityConnected
{
    public function __construct(
        private RegisterStreamerSource $registerSource,
    ) {}

    public function handle(ExternalIdentityConnected $event): void
    {
        $identity = $event->identity;
        $owner = $identity->model;

        if (!$identity->provider->isStreamingPlatform() || !$owner instanceof User) {
            return;
        }

        $streamer = Streamer::query()->whereBelongsTo($owner)->first();

        if ($streamer instanceof Streamer) {
            $this->registerSource->handle($streamer, $identity);
        }
    }
}
