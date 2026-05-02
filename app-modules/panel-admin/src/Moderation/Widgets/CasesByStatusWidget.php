<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Models\ModerationCase;

class CasesByStatusWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending', ModerationCase::query()->where('status', 'pending')->count())
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Resolved (this month)', ModerationCase::query()
                ->where('status', 'resolved')
                ->where('resolved_at', '>=', now()->startOfMonth())
                ->count())
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Escalated', ModerationCase::query()->where('status', 'escalated')->count())
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
