<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CharacterRelationManager extends RelationManager
{
    protected static string $relationship = 'character';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->badge(),
                TextColumn::make('reputation')
                    ->searchable()
                    ->badge()
                    ->label('Reputation'),
                TextColumn::make('experience')
                    ->badge()
                    ->label('Experience'),
                TextColumn::make('daily_bonus_claimed_at')
                    ->label('Daily Bonus Claimed At'),
            ]);
    }
}
