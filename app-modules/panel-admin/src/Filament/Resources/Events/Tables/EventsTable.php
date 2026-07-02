<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;

final class EventsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('panel-admin::events.columns.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_type')
                    ->label(__('panel-admin::events.columns.type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label(__('panel-admin::events.columns.tenant'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label(__('panel-admin::events.columns.starts_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label(__('panel-admin::events.columns.ends_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('panel-admin::events.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label(__('panel-admin::events.columns.type'))
                    ->options(EventType::class),

                SelectFilter::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->options(EventStatus::class),
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
