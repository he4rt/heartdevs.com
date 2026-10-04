<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\Streamer\Support\OverlayToken;

final readonly class EnsureStreamer
{
    public function __construct(
        private RegisterStreamerSource $registerSource,
    ) {}

    public function handle(User $user): Streamer
    {
        $streamer = Streamer::withTrashed()->createOrFirst(
            ['user_id' => $user->getKey()],
            ['status' => StreamerStatus::Active, ...OverlayToken::attributes(OverlayToken::generate())],
        );

        if ($streamer->trashed()) {
            $streamer->restore();
        }

        if ($streamer->wasRecentlyCreated) {
            $this->registerConnectedPlatforms($streamer, $user);
        }

        return $streamer;
    }

    private function registerConnectedPlatforms(Streamer $streamer, User $user): void
    {
        $user->providers()
            ->whereIn('provider', IdentityProvider::streamingPlatforms())
            ->activelyConnected()
            ->get()
            ->each(fn (ExternalIdentity $identity): StreamerSource => $this->registerSource->handle($streamer, $identity));
    }
}
