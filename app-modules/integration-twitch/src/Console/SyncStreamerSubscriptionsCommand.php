<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\Actions\SyncStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Description(description: 'Create the missing Twitch EventSub subscriptions of streamer sources and remove the unwanted ones')]
#[Signature(signature: 'twitch:sync-streamer-subscriptions {source? : Streamer source id (defaults to every Twitch source)}')]
final class SyncStreamerSubscriptionsCommand extends Command
{
    public function handle(SyncStreamerTwitchSubscriptions $sync): int
    {
        if (!EventSubWebhook::isConfigured()) {
            $this->error('Set TWITCH_EVENTSUB_SECRET before syncing streamer subscriptions.');

            return self::FAILURE;
        }

        $sourceId = $this->argument('source');

        $sources = StreamerSource::query()
            ->whereHas('identity', fn (Builder $identity): Builder => $identity->where('provider', IdentityProvider::Twitch))
            ->when(is_string($sourceId), fn (Builder $query): Builder => $query->whereKey($sourceId))
            ->with(['identity', 'streamer'])
            ->get();

        if ($sources->isEmpty()) {
            $this->error('No Twitch streamer source found.');

            return self::FAILURE;
        }

        foreach ($sources as $source) {
            $sync->handle($source);

            $this->line(sprintf(
                '@%s: %d subscriptions',
                $source->identity->metadata['username'] ?? $source->identity->external_account_id,
                TwitchSubscription::query()->where('streamer_source_id', $source->getKey())->count(),
            ));
        }

        return self::SUCCESS;
    }
}
