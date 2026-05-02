<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class ModeratorPerformanceWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.moderator_performance.heading');
    }

    public function table(Table $table): Table
    {
        $start = $this->periodStart();

        return $table
            ->query(
                ModerationAction::query()
                    ->where('moderation_actions.created_at', '>=', $start)
                    ->whereNotNull('moderator_id')
                    ->join('moderation_cases', 'moderation_actions.case_id', '=', 'moderation_cases.id')
                    ->selectRaw('moderation_actions.moderator_id')
                    ->selectRaw('count(*) as total_cases')
                    ->selectRaw('AVG(EXTRACT(EPOCH FROM (moderation_actions.created_at - moderation_cases.created_at)) / 60) as avg_minutes')
                    ->selectRaw(
                        '(SELECT count(*) FROM moderation_appeals WHERE moderation_appeals.action_id = ANY(ARRAY_AGG(moderation_actions.id)) AND moderation_appeals.status = ?) as overturned_count',
                        ['overturned']
                    )
                    ->groupBy('moderation_actions.moderator_id')
                    ->orderByDesc('total_cases')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('moderator.username')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.heading'))
                    ->formatStateUsing(fn (ModerationAction $record): string => '@'.($record->moderator?->username ?? '—')),
                TextColumn::make('total_cases')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.cases'))
                    ->numeric(),
                TextColumn::make('avg_minutes')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.avg_time'))
                    ->formatStateUsing(fn (ModerationAction $record): string => ((int) $record->avg_minutes).'min'),
                TextColumn::make('overturned_count')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.overturn'))
                    ->formatStateUsing(function (ModerationAction $record): string {
                        $total = $record->total_cases;
                        $overturned = $record->overturned_count;
                        $rate = $total > 0 ? round(($overturned / $total) * 100) : 0;

                        return $rate.'%';
                    }),
            ])
            ->paginated(false);
    }

    protected function getTableDescription(): ?string
    {
        $start = $this->periodStart();

        $totalActions = ModerationAction::query()
            ->where('created_at', '>=', $start)
            ->count();

        $overturned = ModerationAppeal::query()
            ->where('created_at', '>=', $start)
            ->where('status', 'overturned')
            ->count();

        $rate = $totalActions > 0 ? round(($overturned / $totalActions) * 100) : 0;

        return __('panel-admin::moderation.dashboard.moderator_performance.overall_overturn', ['rate' => $rate]);
    }
}
