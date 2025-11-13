<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels\Widgets;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Events\Filament\App\EventModels\EventModelResource;
use He4rt\Events\Models\EventModel;
use Illuminate\Database\Eloquent\Builder;

class LatestEvents extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => EventModel::query()->where('tenant_id', Filament::getTenant()->getKey())->latest())
            ->columns([
                TextColumn::make('event_type')
                    ->badge()
                    ->searchable(),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('event_at')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y'))
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Event Hour')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i'))
                    ->description(fn ($record) => $record->end_at->format('d/m/Y H:i'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('Details')
                    ->label('Details')
                    ->icon('heroicon-s-eye')
                    ->action(fn (EventModel $record) => $this->redirect(EventModelResource::getUrl('show', ['record' => $record->getKey()]))),
            ]);
    }
}
