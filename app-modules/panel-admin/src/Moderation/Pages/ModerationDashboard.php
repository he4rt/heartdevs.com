<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use Filament\Pages\Page;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;

class ModerationDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Moderation';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'panel-admin::filament-page';

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
