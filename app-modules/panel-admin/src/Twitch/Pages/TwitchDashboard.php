<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Twitch\TwitchCluster;
use He4rt\PanelAdmin\Twitch\Widgets\EventsByTypeChartWidget;
use He4rt\PanelAdmin\Twitch\Widgets\EventsPerDayChartWidget;
use He4rt\PanelAdmin\Twitch\Widgets\TwitchStatsWidget;

class TwitchDashboard extends Page
{
    protected static ?string $cluster = TwitchCluster::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    protected string $view = 'panel-admin::twitch.dashboard';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::twitch.navigation.dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::twitch.navigation.group_overview');
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TwitchStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            EventsPerDayChartWidget::class,
            EventsByTypeChartWidget::class,
        ];
    }
}
