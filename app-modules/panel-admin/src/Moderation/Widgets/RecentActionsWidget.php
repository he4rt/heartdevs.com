<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class RecentActionsWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.recent_actions');
    }

    public function table(Table $table): Table
    {
        $start = $this->periodStart();

        return $table
            ->query(
                ModerationAction::query()
                    ->with(['moderator', 'case'])
                    ->where('created_at', '>=', $start)
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('action_type')
                    ->badge(),
                TextColumn::make('moderator.username')
                    ->label('Moderator')
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—'),
                TextColumn::make('case.violation_type')
                    ->label('Violation')
                    ->badge(),
                IconColumn::make('automated')
                    ->boolean()
                    ->label('Automated'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('When')
                    ->sortable(),
            ])
            ->paginated(condition: false);
    }
}
