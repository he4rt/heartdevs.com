<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Activity\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

class ExternalIdentityStatsOverview extends StatsOverviewWidget
{
    protected int|array|null $columns = 6;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Identities', ExternalIdentity::query()->count())
                ->icon(Heroicon::Link),
            Stat::make('Active Connections', ExternalIdentity::query()
                ->whereNotNull('connected_at')
                ->whereNull('disconnected_at')
                ->count())
                ->icon(Heroicon::CheckCircle)
                ->color('success'),
            Stat::make('Disconnected', ExternalIdentity::query()
                ->whereNotNull('disconnected_at')
                ->count())
                ->icon(Heroicon::XCircle)
                ->color('danger'),
            Stat::make('Discord', ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->count())
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info'),
            Stat::make('Twitch', ExternalIdentity::query()
                ->where('provider', IdentityProvider::Twitch)
                ->count())
                ->icon('heroicon-o-video-camera')
                ->color('purple'),
            Stat::make('Total Messages', Message::query()
                ->whereNotNull('external_identity_id')
                ->count())
                ->icon(Heroicon::ChatBubbleLeftEllipsis)
                ->color('gray'),
        ];
    }
}
