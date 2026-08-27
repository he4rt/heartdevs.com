<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Event\Models\Event;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions\GenerateCheckInCodeAction;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions\RevokeCheckInCodeAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CheckInCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'checkInCodes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::events.relations.check_in_codes');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): string
    {
        /** @var Event $ownerRecord */
        return (string) $ownerRecord->checkInCodes()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest())
            ->columns([
                TextColumn::make('code')
                    ->label(__('panel-admin::events.columns.code'))
                    ->badge()
                    ->color(fn (CheckInCode $record): string => $record->revoked_at !== null ? 'gray' : ($record->expires_at->isPast() ? 'warning' : 'success'))
                    ->searchable(),

                TextColumn::make('event_date')
                    ->label(__('panel-admin::events.columns.event_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label(__('panel-admin::events.columns.valid_from'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('panel-admin::events.columns.expires_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('uses_count')
                    ->label(__('panel-admin::events.columns.uses'))
                    ->state(fn (CheckInCode $record): string => $record->uses_count.($record->max_uses !== null ? '/'.$record->max_uses : '')),

                TextColumn::make('revoked_at')
                    ->label(__('panel-admin::events.columns.revoked_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->headerActions([
                GenerateCheckInCodeAction::make(),
            ])
            ->recordActions([
                RevokeCheckInCodeAction::make(),
            ]);
    }
}
