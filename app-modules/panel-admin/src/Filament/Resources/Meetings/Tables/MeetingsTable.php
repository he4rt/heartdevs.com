<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meetingType.name')
                    ->badge()
                    ->color('primary')
                    ->label('Type'),
                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('admin.username')
                    ->label('Host'),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->placeholder('Ongoing'),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Participants'),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('endMeeting')
                    ->label('End Meeting')
                    ->icon(Heroicon::Stop)
                    ->color('warning')
                    ->visible(fn ($record) => $record->ends_at === null)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['ends_at' => now()]))
                    ->successNotificationTitle('Meeting ended'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
