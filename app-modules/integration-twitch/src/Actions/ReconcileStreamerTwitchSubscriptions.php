<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Collection;
use Saloon\Exceptions\SaloonException;

/**
 * Twitch does not call the webhook when a verification fails, so local statuses can go stale.
 */
final readonly class ReconcileStreamerTwitchSubscriptions
{
    public function __construct(
        private TwitchHelixConnector $helix,
        private SyncTwitchSubscriptionAction $storeSubscription,
    ) {}

    /**
     * @throws TwitchUnreachable
     */
    public function handle(StreamerSource $source): void
    {
        $broadcasterId = $source->identity->external_account_id;

        if ($broadcasterId === null) {
            return;
        }

        $remote = $this->remoteSubscriptions($broadcasterId);
        $local = TwitchSubscription::query()->where('broadcaster_user_id', $broadcasterId)->get();

        foreach ($local as $subscription) {
            $this->applyRemoteStatus($subscription, $remote->get($subscription->subscription_id));
        }

        $remote
            ->reject(fn (array $subscription, string $id): bool => $local->contains('subscription_id', $id))
            ->filter(fn (array $subscription): bool => data_get($subscription, 'transport.callback') === EventSubWebhook::callbackUrl())
            ->each(fn (array $subscription): bool => ($this->storeSubscription)($subscription)->update(['streamer_source_id' => $source->getKey()]));
    }

    /**
     * @param  array<string, mixed>|null  $remote
     */
    private function applyRemoteStatus(TwitchSubscription $subscription, ?array $remote): void
    {
        if ($remote === null) {
            $subscription->delete();

            return;
        }

        $status = is_string($remote['status'] ?? null) ? TwitchSubscriptionStatus::tryFrom($remote['status']) : null;

        if ($status instanceof TwitchSubscriptionStatus && $status !== $subscription->status) {
            $subscription->update(['status' => $status]);
        }
    }

    /**
     * @return Collection<string, array<string, mixed>>
     *
     * @throws TwitchUnreachable
     */
    private function remoteSubscriptions(string $broadcasterId): Collection
    {
        $subscriptions = [];
        $cursor = null;

        do {
            try {
                $response = $this->helix->send(new ListSubscriptions(userId: $broadcasterId, after: $cursor));
            } catch (SaloonException $saloonException) {
                throw TwitchUnreachable::while('listing EventSub subscriptions', $saloonException);
            }

            $page = $response->json('data', []);
            $subscriptions = [...$subscriptions, ...(is_array($page) ? $page : [])];

            $nextCursor = $response->json('pagination.cursor');
            $cursor = is_string($nextCursor) && $nextCursor !== '' ? $nextCursor : null;
        } while ($cursor !== null);

        /** @var Collection<string, array<string, mixed>> */
        return collect($subscriptions)
            ->filter(fn (mixed $subscription): bool => is_array($subscription) && is_string($subscription['id'] ?? null))
            ->filter(fn (array $subscription): bool => in_array($broadcasterId, [
                data_get($subscription, 'condition.broadcaster_user_id'),
                data_get($subscription, 'condition.to_broadcaster_user_id'),
            ], strict: true))
            ->keyBy('id');
    }
}
