<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Listeners;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityDisconnected;
use He4rt\IntegrationTwitch\Actions\SyncStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\Streaming\Streamer\Events\StreamerActivated;
use He4rt\Streaming\Streamer\Events\StreamerDisabled;
use He4rt\Streaming\Streamer\Events\StreamerSourceRegistered;
use He4rt\Streaming\Streamer\Events\StreamerSourceUpdated;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Collection;

final readonly class SyncStreamerSubscriptionsOnChange implements ShouldQueueAfterCommit
{
    public function handle(
        ExternalIdentityConnected|ExternalIdentityDisconnected|StreamerActivated|StreamerDisabled|StreamerSourceRegistered|StreamerSourceUpdated $event,
    ): void {
        if (!EventSubWebhook::isConfigured()) {
            return;
        }

        $twitchSources = $this->affectedSources($event)
            ->filter(fn (StreamerSource $source): bool => $source->identity->provider === IdentityProvider::Twitch);

        if ($twitchSources->isEmpty()) {
            return;
        }

        $sync = resolve(SyncStreamerTwitchSubscriptions::class);

        $twitchSources->each($sync->handle(...));
    }

    /**
     * @return Collection<int, StreamerSource>
     */
    private function affectedSources(
        ExternalIdentityConnected|ExternalIdentityDisconnected|StreamerActivated|StreamerDisabled|StreamerSourceRegistered|StreamerSourceUpdated $event,
    ): Collection {
        return match (true) {
            $event instanceof ExternalIdentityConnected,
            $event instanceof ExternalIdentityDisconnected => StreamerSource::query()->where('external_identity_id', $event->identity->getKey())->get(),
            $event instanceof StreamerActivated,
            $event instanceof StreamerDisabled => $event->streamer->sources()->get(),
            default => new Collection([$event->source]),
        };
    }
}
