<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Community\Feedback\Models\Feedback;

class PendingReviewsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Feedback Reviews';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Feedback::query()
                    ->whereDoesntHave('review')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('sender.username')->label('From'),
                TextColumn::make('target.username')->label('To'),
                TextColumn::make('type')->badge(),
                TextColumn::make('message')->limit(60),
                TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
