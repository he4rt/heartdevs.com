<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Widgets\DiscordStatsWidget;
use He4rt\PanelAdmin\Discord\Widgets\EventsPerDayChartWidget;
use He4rt\PanelAdmin\Discord\Widgets\MemberGrowthChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class DiscordDashboard extends Page
{
    protected static ?string $cluster = DiscordCluster::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    protected string $view = 'panel-admin::discord.dashboard';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_overview');
    }

    public function getTitle(): string|Htmlable
    {
        return __('panel-admin::discord.dashboard.heading');
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DiscordStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            EventsPerDayChartWidget::class,
            MemberGrowthChartWidget::class,
        ];
    }
}
