<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CharactersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->searchable()
                    ->label('User'),

                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray')
                    ->label('Tenant'),

                TextColumn::make('level'),

                TextColumn::make('experience')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('reputation')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('daily_bonus_claimed_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('grantXp')
                    ->label('Grant XP')
                    ->icon(Heroicon::ArrowTrendingUp)
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->required()
                            ->integer()
                            ->minValue(1),
                    ])
                    ->action(fn ($record, array $data) => $record->increment('experience', (int) $data['amount']))
                    ->successNotificationTitle('XP granted successfully'),
                Action::make('resetDailyBonus')
                    ->label('Reset Daily Bonus')
                    ->icon(Heroicon::ArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['daily_bonus_claimed_at' => null]))
                    ->successNotificationTitle('Daily bonus reset'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('grantXpBulk')
                        ->label('Grant XP')
                        ->icon(Heroicon::ArrowTrendingUp)
                        ->color('success')
                        ->form([
                            TextInput::make('amount')
                                ->required()
                                ->integer()
                                ->minValue(1),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn ($record) => $record->increment('experience', (int) $data['amount']));
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('XP granted to selected characters'),
                ]),
            ]);
    }
}
