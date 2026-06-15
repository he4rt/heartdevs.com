<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;
use Illuminate\Database\Query\Builder;

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
                User::query()
                    ->whereIn('id', static function (Builder $query) use ($start): void {
                        $query->select('moderator_id')
                            ->from('moderation_actions')
                            ->where('created_at', '>=', $start)
                            ->whereNotNull('moderator_id')
                            ->groupBy('moderator_id')
                            ->orderByRaw('count(*) desc')
                            ->limit(10);
                    })
            )
            ->columns([
                TextColumn::make('username')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.heading'))
                    ->formatStateUsing(fn (string $state): string => '@'.$state),
                TextColumn::make('total_cases')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.cases'))
                    ->state(fn (User $record) => ModerationAction::query()
                        ->where('moderator_id', $record->id)
                        ->where('created_at', '>=', $start)
                        ->count()),
                TextColumn::make('avg_time')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.avg_time'))
                    ->state(static function (User $record) use ($start): string {
                        $avg = (int) ModerationAction::query()
                            ->where('moderation_actions.moderator_id', $record->id)
                            ->where('moderation_actions.created_at', '>=', $start)
                            ->join('moderation_cases', 'moderation_actions.case_id', '=', 'moderation_cases.id')
                            ->selectRaw('AVG(EXTRACT(EPOCH FROM (moderation_actions.created_at - moderation_cases.created_at)) / 60) as avg_minutes')
                            ->value('avg_minutes');

                        return $avg.'min';
                    }),
                TextColumn::make('overturn_rate')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.overturn'))
                    ->state(static function (User $record) use ($start): string {
                        $actionIds = ModerationAction::query()
                            ->where('moderator_id', $record->id)
                            ->where('created_at', '>=', $start)
                            ->pluck('id');

                        $totalActions = $actionIds->count();

                        $overturned = ModerationAppeal::query()
                            ->whereIn('action_id', $actionIds)
                            ->where('status', 'overturned')
                            ->count();

                        $rate = $totalActions > 0 ? round(($overturned / $totalActions) * 100) : 0;

                        return $rate.'%';
                    }),
            ])
            ->paginated(false)
            ->defaultSort(null);
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
