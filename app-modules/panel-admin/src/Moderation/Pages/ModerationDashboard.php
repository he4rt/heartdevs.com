<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;
use UnitEnum;

class ModerationDashboard extends Page
{
    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Overview';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    protected string $view = 'panel-admin::filament-page';

    public function getHeaderWidgetsColumns(): int
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CasesByStatusWidget::class,
            RecentActionsWidget::class,
        ];
    }
}
