<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\Enums\Talks\TalkStatusEnum;

class EventSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.title')
                    ->searchable()
                    ->label('Event'),
                TextColumn::make('user.username')
                    ->searchable()
                    ->label('Speaker'),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('field_type'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TalkStatusEnum::class),
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
