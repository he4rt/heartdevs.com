<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Models\ModerationAction;

class RecentActionsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Actions';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ModerationAction::query()
                    ->with(['moderator', 'case'])
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('action_type')
                    ->badge(),
                TextColumn::make('moderator.name')
                    ->label('Moderator'),
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
            ->paginated(false);
    }
}
