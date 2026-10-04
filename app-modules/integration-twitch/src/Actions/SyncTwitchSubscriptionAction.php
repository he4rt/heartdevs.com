<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\IntegrationTwitch\Models\TwitchSubscription;

final readonly class SyncTwitchSubscriptionAction
{
    /**
     * @param  array<string, mixed>  $subscription
     */
    public function __invoke(array $subscription): TwitchSubscription
    {
        /** @var array<string, string> $condition */
        $condition = is_array($subscription['condition'] ?? null) ? $subscription['condition'] : [];

        /** @var array<string, string> $transport */
        $transport = is_array($subscription['transport'] ?? null) ? $subscription['transport'] : [];

        return TwitchSubscription::query()->updateOrCreate(
            ['subscription_id' => $subscription['id']],
            [
                'type' => $subscription['type'],
                'status' => $subscription['status'],
                'broadcaster_user_id' => $condition['broadcaster_user_id']
                    ?? $condition['to_broadcaster_user_id']
                    ?? '',
                'condition' => $condition,
                'transport' => $transport['method'] ?? 'webhook',
                'callback_url' => $transport['callback'] ?? null,
                'cost' => $subscription['cost'] ?? 0,
                'version' => $subscription['version'] ?? '1',
            ]
        );
    }
}
