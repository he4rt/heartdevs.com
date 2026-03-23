<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('content')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->content),
                TextColumn::make('channel_id')
                    ->label('Channel')
                    ->copyable(),
                TextColumn::make('obtained_experience')
                    ->label('XP')
                    ->numeric(0),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
