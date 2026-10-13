<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSettings;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class UnmuteChatterOnOverlay
{
    public function __construct(
        private UpdateStreamerSettings $updateSettings,
    ) {}

    public function handle(Streamer $streamer, IdentityProvider $platform, string $chatterId): void
    {
        $settings = $streamer->settings;

        $this->updateSettings->handle($streamer, $settings->withMutedChatters($settings->mutedChatters->without($platform, $chatterId)));
    }
}
