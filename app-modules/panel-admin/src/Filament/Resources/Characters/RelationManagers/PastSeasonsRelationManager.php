<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PastSeasonsRelationManager extends RelationManager
{
    protected static string $relationship = 'pastSeasons';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('season.name'),

                TextColumn::make('ranking_position')
                    ->sortable()
                    ->label('#'),

                TextColumn::make('experience')
                    ->numeric(0),

                TextColumn::make('messages_count')
                    ->numeric(0),

                TextColumn::make('badges_count')
                    ->numeric(0),
            ]);
    }
}
