<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Messages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('provider.user.username')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('channel_id'),
                TextColumn::make('content')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->content)
                    ->searchable(),
                TextColumn::make('obtained_experience')
                    ->numeric(0)
                    ->label('XP'),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
