<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Health;

use App\Enums\FilamentPanel;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\IntegrationTwitch\OAuth\TwitchStreamerFeature;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class TwitchHealthReport
{
    public function __construct(
        private CheckTwitchAccount $account,
        private CheckWebhookAddress $webhook,
        private CheckTwitchSubscriptions $subscriptions,
        private CheckLastTwitchEvent $lastEvent,
    ) {}

    /**
     * @return array<int, HealthCheck>
     */
    public function for(StreamerSource $source): array
    {
        $requiredScopes = TwitchScopes::requestedFor(FilamentPanel::App->value, $source->streamer->user, TwitchStreamerFeature::neededFor($source->chat_reader));

        return [
            $this->account->handle($source->identity, $requiredScopes),
            $this->webhook->handle(),
            $this->subscriptions->handle($source),
            $this->lastEvent->handle($source),
        ];
    }
}
