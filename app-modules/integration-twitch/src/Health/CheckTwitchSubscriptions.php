<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Health;

use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Support\StreamerSubscriptionPlan;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthFix;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final class CheckTwitchSubscriptions
{
    public const string KEY = 'twitch_subscriptions';

    private const string TITLE = 'Inscrições';

    public function handle(StreamerSource $source): HealthCheck
    {
        $expected = count(StreamerSubscriptionPlan::for($source));
        $subscriptions = TwitchSubscription::query()->where('streamer_source_id', $source->getKey())->oldest()->get();

        $active = $subscriptions->where('status', TwitchSubscriptionStatus::Enabled)->count();
        $awaiting = $subscriptions->filter(fn (TwitchSubscription $subscription): bool => $subscription->isAwaitingVerification())->count();
        $stuck = $subscriptions->filter(fn (TwitchSubscription $subscription): bool => $subscription->isStuckInVerification());
        $revoked = $subscriptions->filter(fn (TwitchSubscription $subscription): bool => $subscription->status->needsReconnect())->count();
        $failed = $subscriptions->reject(fn (TwitchSubscription $subscription): bool => $subscription->isWorking())->count() - $stuck->count() - $revoked;
        $missing = max(0, $expected - $active - $awaiting);

        $summary = sprintf('%d de %d ativas', $active, $expected);
        $stuckForMinutes = (int) ($stuck->first()?->created_at?->diffInMinutes(now()) ?? 0);

        return match (true) {
            $revoked > 0 => new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, sprintf('%s · a Twitch revogou %d. Conecte de novo.', $summary, $revoked), HealthFix::Reconnect),
            $failed > 0 => new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, sprintf('%s · %d com falha na Twitch', $summary, $failed), HealthFix::RepairSubscriptions),
            $stuck->isNotEmpty() => new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, sprintf('%s · %d pendentes há %d min', $summary, $stuck->count(), $stuckForMinutes), HealthFix::RepairSubscriptions),
            $missing > 0 => new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, sprintf('%s · faltam %d', $summary, $missing), HealthFix::RepairSubscriptions),
            $awaiting > 0 => new HealthCheck(self::KEY, HealthStatus::Waiting, self::TITLE, sprintf('%s · %d aguardando a Twitch confirmar', $summary, $awaiting)),
            default => new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, $summary),
        };
    }
}
