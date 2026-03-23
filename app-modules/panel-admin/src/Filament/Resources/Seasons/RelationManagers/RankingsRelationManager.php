<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RankingsRelationManager extends RelationManager
{
    protected static string $relationship = 'rankings';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('ranking_position')
            ->columns([
                TextColumn::make('ranking_position')
                    ->sortable()
                    ->label('#'),

                TextColumn::make('character.user.username')
                    ->label('User'),

                TextColumn::make('experience')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('messages_count')
                    ->numeric(0),

                TextColumn::make('badges_count')
                    ->numeric(0),
            ]);
    }
}
