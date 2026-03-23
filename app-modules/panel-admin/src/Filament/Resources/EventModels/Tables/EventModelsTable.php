<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\Events\Enums\EventTypeEnum;

class EventModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('event_type')
                    ->badge(),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('event_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('attendees_count')
                    ->numeric(0)
                    ->label('Attendees'),
                TextColumn::make('waitlist_count')
                    ->numeric(0)
                    ->label('Waitlist'),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('event_type')
                    ->options(EventTypeEnum::class),
                TernaryFilter::make('active'),
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
