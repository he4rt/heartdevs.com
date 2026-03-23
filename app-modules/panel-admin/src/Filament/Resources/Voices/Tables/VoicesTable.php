<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Voices\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('provider.user.username')
                    ->label('User'),
                TextColumn::make('channel_name')
                    ->searchable(),
                TextColumn::make('state')
                    ->badge(),
                TextColumn::make('obtained_experience')
                    ->numeric(0)
                    ->label('XP'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options([
                        'disabled' => 'Disabled',
                        'muted' => 'Muted',
                        'unmuted' => 'Unmuted',
                    ]),
            ]);
    }
}
