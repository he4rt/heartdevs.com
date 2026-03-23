<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('attend_order')
                    ->sortable()
                    ->label('#'),
            ]);
    }
}
