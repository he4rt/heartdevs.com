<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('name'),
                TextColumn::make('attend_at')
                    ->dateTime()
                    ->label('Attended At'),
            ]);
    }
}
