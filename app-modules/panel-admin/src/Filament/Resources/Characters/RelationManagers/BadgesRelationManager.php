<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BadgesRelationManager extends RelationManager
{
    protected static string $relationship = 'badges';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('provider')
                    ->badge(),

                TextColumn::make('claimed_at')
                    ->dateTime()
                    ->label('Claimed'),
            ]);
    }
}
