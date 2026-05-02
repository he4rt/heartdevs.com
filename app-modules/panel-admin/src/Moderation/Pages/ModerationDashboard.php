<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Widgets\AppealSlaWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByPlatformChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\FalsePositiveRateWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModerationStatsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModeratorPerformanceWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\TopViolationTypesChartWidget;

class ModerationDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_overview');
    }

    public function getTitle(): string
    {
        return __('panel-admin::moderation.dashboard.heading');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('period')
                    ->label(__('panel-admin::moderation.dashboard.filter_period'))
                    ->options(__('panel-admin::moderation.dashboard.periods'))
                    ->default('30d'),
            ])->columns(3),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            ModerationStatsWidget::class,
            CasesByStatusChartWidget::class,
            CasesByPlatformChartWidget::class,
            TopViolationTypesChartWidget::class,
            FalsePositiveRateWidget::class,
            ModeratorPerformanceWidget::class,
            AppealSlaWidget::class,
            RecentActionsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
