<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\Event\Enums\EventType;

final class EventsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->sortable(),

                IconColumn::make('active')
                    ->label('Published')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options(EventType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
