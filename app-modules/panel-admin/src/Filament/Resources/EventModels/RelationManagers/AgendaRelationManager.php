<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgendaRelationManager extends RelationManager
{
    protected static string $relationship = 'agenda';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schedulable_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextColumn::make('starting_at')
                    ->sortable(),
                TextColumn::make('ending_at'),
            ]);
    }
}
