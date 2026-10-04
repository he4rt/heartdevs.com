<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchBotTokenService;
use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;
use Symfony\Component\HttpFoundation\Response;

final readonly class SyncStreamerTwitchSubscriptions
{
    private const array ALERT_TYPES = [
        TwitchEventSubType::StreamOnline,
        TwitchEventSubType::StreamOffline,
        TwitchEventSubType::ChannelUpdate,
        TwitchEventSubType::ChannelFollow,
        TwitchEventSubType::ChannelSubscribe,
        TwitchEventSubType::ChannelSubscriptionMessage,
        TwitchEventSubType::ChannelSubscriptionGift,
        TwitchEventSubType::ChannelCheer,
        TwitchEventSubType::ChannelRaid,
    ];

    private const array CHAT_TYPES = [
        TwitchEventSubType::ChannelChatMessage,
        TwitchEventSubType::ChannelChatMessageDelete,
    ];

    public function __construct(
        private TwitchHelixConnector $helix,
    ) {}

    public function handle(StreamerSource $source): void
    {
        $broadcasterId = $source->identity->external_account_id;
        $desired = $this->desiredSubscriptions($source, $broadcasterId);

        TwitchSubscription::query()
            ->where('streamer_source_id', $source->getKey())
            ->get()
            ->reject(fn (TwitchSubscription $subscription): bool => array_key_exists($this->keyOf($subscription->type, $subscription->condition), $desired))
            ->each($this->remove(...));

        $existingKeys = TwitchSubscription::query()
            ->where('broadcaster_user_id', $broadcasterId)
            ->whereIn('status', [TwitchSubscriptionStatus::Enabled, TwitchSubscriptionStatus::VerificationPending])
            ->get()
            ->map(fn (TwitchSubscription $subscription): string => $this->keyOf($subscription->type, $subscription->condition))
            ->all();

        foreach ($desired as $key => [$type, $condition]) {
            if (!in_array($key, $existingKeys, strict: true)) {
                $this->create($source, $broadcasterId, $type, $condition);
            }
        }
    }

    /**
     * Twitch echoes unused condition fields as empty strings, so they are dropped before comparing.
     *
     * @param  array<string, mixed>  $condition
     */
    private function keyOf(string $type, array $condition): string
    {
        $filled = array_filter($condition, fn (mixed $value): bool => is_string($value) && $value !== '');
        ksort($filled);

        return $type.'|'.json_encode($filled, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, array{TwitchEventSubType, array<string, string>}>
     */
    private function desiredSubscriptions(StreamerSource $source, string $broadcasterId): array
    {
        $isSyncable = $source->identity->provider === IdentityProvider::Twitch
            && $source->streamer->isActive()
            && $source->identity()->activelyConnected()->exists();

        if (!$isSyncable) {
            return [];
        }

        $chatUserId = match ($source->chat_reader) {
            ChatReader::OwnAccount => $broadcasterId,
            ChatReader::He4rtBot => TwitchBotTokenService::userId(),
            null => null,
        };

        $wanted = array_map(fn (TwitchEventSubType $type): array => [$type, $type->getCondition($broadcasterId)], self::ALERT_TYPES);

        if ($chatUserId !== null) {
            foreach (self::CHAT_TYPES as $type) {
                $wanted[] = [$type, $type->getCondition($broadcasterId, $chatUserId)];
            }
        }

        $desired = [];

        foreach ($wanted as [$type, $condition]) {
            $desired[$this->keyOf($type->value, $condition)] = [$type, $condition];
        }

        return $desired;
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
