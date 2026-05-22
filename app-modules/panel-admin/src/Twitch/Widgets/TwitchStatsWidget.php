<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;

class TwitchStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenantId = filament()->getTenant()?->getKey();

        $totalEvents = TwitchEventLog::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->count();

        $eventsToday = TwitchEventLog::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', today())
            ->count();

        $activeSubs = TwitchSubscription::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', TwitchSubscriptionStatus::Enabled)
            ->count();

        $errorSubs = TwitchSubscription::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereNotIn('status', [
                TwitchSubscriptionStatus::Enabled->value,
                TwitchSubscriptionStatus::VerificationPending->value,
            ])
            ->count();

        return [
            Stat::make(__('panel-admin::twitch.dashboard.stats.total_events'), $totalEvents)
                ->description(__('panel-admin::twitch.dashboard.stats.total_events_desc'))
                ->icon(Heroicon::OutlinedSignal)
                ->color(Color::Blue),
            Stat::make(__('panel-admin::twitch.dashboard.stats.events_today'), $eventsToday)
                ->description(__('panel-admin::twitch.dashboard.stats.events_today_desc'))
                ->icon(Heroicon::OutlinedBolt)
                ->color(Color::Amber),
            Stat::make(__('panel-admin::twitch.dashboard.stats.active_subs'), $activeSubs)
                ->description(__('panel-admin::twitch.dashboard.stats.active_subs_desc'))
                ->icon(Heroicon::OutlinedBell)
                ->color(Color::Green),
            Stat::make(__('panel-admin::twitch.dashboard.stats.error_subs'), $errorSubs)
                ->description(__('panel-admin::twitch.dashboard.stats.error_subs_desc'))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($errorSubs > 0 ? Color::Red : Color::Gray),
        ];
    }
}
