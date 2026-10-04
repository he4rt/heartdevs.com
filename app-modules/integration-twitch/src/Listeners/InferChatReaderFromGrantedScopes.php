<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Listeners;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\OAuth\TwitchStreamerFeature;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSource;
use He4rt\Streaming\Streamer\Events\StreamerSourceRegistered;

final readonly class InferChatReaderFromGrantedScopes
{
    public function __construct(
        private UpdateStreamerSource $updateSource,
    ) {}

    public function handle(StreamerSourceRegistered $event): void
    {
        $source = $event->source;
        $identity = $source->identity;
        $isUndecidedTwitchSource = $identity->provider === IdentityProvider::Twitch && $source->chat_reader === null;

        if (!$isUndecidedTwitchSource) {
            return;
        }

        $grantedScopes = $identity->metadata['granted_scopes'] ?? [];
        $chatReader = TwitchStreamerFeature::chatReaderFrom(is_array($grantedScopes) ? array_values(array_filter($grantedScopes, is_string(...))) : []);

        if ($chatReader instanceof ChatReader) {
            $this->updateSource->handle($source, $source->enabled, $chatReader);
        }
    }
}
