<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\IntegrationDiscord\Enums\DiscordChannelType;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordMember;

class DiscordStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $activeMembers = DiscordMember::query()
            ->whereNull('left_at')
            ->where('is_bot', operator: false)
            ->count();

        $joins7d = DiscordMember::query()
            ->where('joined_at', '>=', now()->subDays(7))
            ->count();

        $leaves7d = DiscordMember::query()
            ->where('left_at', '>=', now()->subDays(7))
            ->count();

        $events24h = DiscordEventLog::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $boosters = DiscordMember::query()
            ->whereNotNull('premium_since')
            ->whereNull('left_at')
            ->count();

        $channels = DiscordChannel::query()
            ->where('type', '!=', DiscordChannelType::GuildCategory)
            ->count();

        return [
            Stat::make(__('panel-admin::discord.dashboard.stats.active_members'), $activeMembers)
                ->description(__('panel-admin::discord.dashboard.stats.active_members_desc'))
                ->icon(Heroicon::OutlinedUsers)
                ->color(Color::Blue),
            Stat::make(__('panel-admin::discord.dashboard.stats.joins_7d'), $joins7d)
                ->description(__('panel-admin::discord.dashboard.stats.joins_7d_desc'))
                ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->color(Color::Green),
            Stat::make(__('panel-admin::discord.dashboard.stats.leaves_7d'), $leaves7d)
                ->description(__('panel-admin::discord.dashboard.stats.leaves_7d_desc'))
                ->icon(Heroicon::OutlinedArrowLeftStartOnRectangle)
                ->color(Color::Red),
            Stat::make(__('panel-admin::discord.dashboard.stats.events_24h'), $events24h)
                ->description(__('panel-admin::discord.dashboard.stats.events_24h_desc'))
                ->icon(Heroicon::OutlinedBolt)
                ->color(Color::Amber),
            Stat::make(__('panel-admin::discord.dashboard.stats.boosters'), $boosters)
                ->description(__('panel-admin::discord.dashboard.stats.boosters_desc'))
                ->icon(Heroicon::OutlinedSparkles)
                ->color(Color::Fuchsia),
            Stat::make(__('panel-admin::discord.dashboard.stats.channels'), $channels)
                ->description(__('panel-admin::discord.dashboard.stats.channels_desc'))
                ->icon(Heroicon::OutlinedHashtag)
                ->color(Color::Gray),
        ];
    }
}
