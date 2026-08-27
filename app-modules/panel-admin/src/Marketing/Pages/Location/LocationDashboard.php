<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use He4rt\PanelAdmin\Marketing\Widgets\LocationMapWidget;
use He4rt\PanelAdmin\Marketing\Widgets\LocationStatsWidget;
use He4rt\PanelAdmin\Marketing\Widgets\TopStatesWidget;

class LocationDashboard extends Page
{
    protected static ?string $cluster = MarketingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'location';

    protected string $view = 'panel-admin::marketing.location-dashboard';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::location.navigation.title');
    }

    public function getTitle(): string
    {
        return __('panel-admin::location.navigation.title');
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'lg' => 5];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            LocationStatsWidget::class,
            LocationMapWidget::class,
            TopStatesWidget::class,
        ];
    }
}
