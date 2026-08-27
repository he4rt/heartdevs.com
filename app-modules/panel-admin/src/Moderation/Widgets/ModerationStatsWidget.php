<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class ModerationStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $start = $this->periodStart();

        $pending = ModerationCase::query()
            ->where('status', 'pending')
            ->count();

        $resolved = ModerationCase::query()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', $start)
            ->count();

        $avgMinutes = (int) ModerationCase::query()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', $start)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        $actionsCount = ModerationAction::query()
            ->where('created_at', '>=', $start)
            ->count();

        $appealsCount = ModerationAppeal::query()
            ->where('created_at', '>=', $start)
            ->count();

        $appealRate = $actionsCount > 0
            ? round(($appealsCount / $actionsCount) * 100)
            : 0;

        return [
            Stat::make(__('panel-admin::moderation.dashboard.stats.pending'), $pending)
                ->description(__('panel-admin::moderation.dashboard.stats.pending_desc'))
                ->icon(Heroicon::OutlinedClock)
                ->color(Color::Amber),
            Stat::make(__('panel-admin::moderation.dashboard.stats.resolved'), $resolved)
                ->description(__('panel-admin::moderation.dashboard.stats.resolved_desc'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color(Color::Green),
            Stat::make(__('panel-admin::moderation.dashboard.stats.avg_time'), $avgMinutes)
                ->description(__('panel-admin::moderation.dashboard.stats.avg_time_desc'))
                ->icon(Heroicon::OutlinedClock)
                ->color(Color::Blue),
            Stat::make(__('panel-admin::moderation.dashboard.stats.appeal_rate'), $appealRate.'%')
                ->description(__('panel-admin::moderation.dashboard.stats.appeal_rate_desc'))
                ->icon(Heroicon::OutlinedScale)
                ->color(Color::Purple),
        ];
    }
}
