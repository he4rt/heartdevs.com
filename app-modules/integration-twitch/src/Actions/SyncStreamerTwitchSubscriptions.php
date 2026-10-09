<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\IntegrationTwitch\Support\StreamerSubscriptionPlan;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;
use Symfony\Component\HttpFoundation\Response;

final readonly class SyncStreamerTwitchSubscriptions
{
    public function __construct(
        private TwitchHelixConnector $helix,
    ) {}

    public function handle(StreamerSource $source): void
    {
        $broadcasterId = $source->identity->external_account_id;
        $desired = StreamerSubscriptionPlan::for($source);

        $isUnwanted = fn (TwitchSubscription $subscription): bool => $subscription->streamer_source_id === $source->getKey()
            && !array_key_exists(StreamerSubscriptionPlan::keyOf($subscription->type, $subscription->condition), $desired);

        [$toRemove, $kept] = TwitchSubscription::query()
            ->where('broadcaster_user_id', $broadcasterId)
            ->get()
            ->partition(fn (TwitchSubscription $subscription): bool => $isUnwanted($subscription) || !$subscription->isWorking());

        $toRemove->each($this->remove(...));

        $existingKeys = $kept
            ->map(fn (TwitchSubscription $subscription): string => StreamerSubscriptionPlan::keyOf($subscription->type, $subscription->condition))
            ->all();

        foreach ($desired as $key => [$type, $condition]) {
            if (!in_array($key, $existingKeys, strict: true)) {
                $this->create($source, $broadcasterId, $type, $condition);
            }
        }
    }

    /**
     * @param  array<string, string>  $condition
     */
    private function create(StreamerSource $source, string $broadcasterId, TwitchEventSubType $type, array $condition): void
    {
        try {
            $data = $this->helix->send(new CreateSubscription(
                type: $type->value,
                version: $type->getVersion(),
                condition: $condition,
                callbackUrl: EventSubWebhook::callbackUrl(),
                secret: EventSubWebhook::secret(),
            ))->json('data.0');
        } catch (RequestException $requestException) {
            Log::warning('Twitch EventSub subscription failed', [
                'type' => $type->value,
                'broadcaster_id' => $broadcasterId,
                'status' => $requestException->getResponse()->status(),
            ]);

            return;
        }

        if (!is_array($data) || !is_string($data['id'] ?? null)) {
            Log::warning('Twitch EventSub returned no subscription', ['type' => $type->value, 'broadcaster_id' => $broadcasterId]);

            return;
        }

        $status = is_string($data['status'] ?? null) ? TwitchSubscriptionStatus::tryFrom($data['status']) : null;

        TwitchSubscription::query()->updateOrCreate(
            ['subscription_id' => $data['id']],
            [
                'type' => $type->value,
                'status' => $status ?? TwitchSubscriptionStatus::VerificationPending,
                'broadcaster_user_id' => $broadcasterId,
                'condition' => is_array($data['condition'] ?? null) ? $data['condition'] : $condition,
                'transport' => 'webhook',
                'callback_url' => EventSubWebhook::callbackUrl(),
                'cost' => is_int($data['cost'] ?? null) ? $data['cost'] : 0,
                'version' => $type->getVersion(),
                'streamer_source_id' => $source->getKey(),
            ],
        );
    }

    private function remove(TwitchSubscription $subscription): void
    {
        try {
            $this->helix->send(new DeleteSubscription($subscription->subscription_id));
        } catch (RequestException $requestException) {
            $isAlreadyGone = $requestException->getResponse()->status() === Response::HTTP_NOT_FOUND;

            if (!$isAlreadyGone) {
                Log::warning('Twitch EventSub removal failed', [
                    'subscription_id' => $subscription->subscription_id,
                    'status' => $requestException->getResponse()->status(),
                ]);

                return;
            }
        }

        $subscription->delete();
    }
}
