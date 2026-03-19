<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Activity\Tracking\Actions\ApproveInteraction;
use He4rt\Activity\Tracking\Actions\RejectInteraction;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;
use He4rt\Activity\Tracking\Models\Interaction;
use Illuminate\Database\Eloquent\Collection;

class InteractionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),

                TextColumn::make('character.user.name')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('value_tier')
                    ->badge()
                    ->label('Tier')
                    ->sortable(),

                TextColumn::make('coins_range')
                    ->label('Coins Range')
                    ->state(fn (Interaction $record): string => sprintf('%d-%d', $record->coins_min, $record->coins_max)),

                TextColumn::make('coins_awarded')
                    ->label('Awarded')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('occurred_at')
                    ->label('Occurred')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ActivityStatus::class),

                SelectFilter::make('type')
                    ->options(ActivityType::class),

                SelectFilter::make('value_tier')
                    ->options(ValueTier::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Interaction $record): bool => $record->status === ActivityStatus::Pending)
                    ->action(fn (Interaction $record) => resolve(ApproveInteraction::class)->handle($record)),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Interaction $record): bool => $record->status === ActivityStatus::Pending)
                    ->action(fn (Interaction $record) => resolve(RejectInteraction::class)->handle($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $approveAction = resolve(ApproveInteraction::class);
                            $records->each(fn (Interaction $record) => $record->status === ActivityStatus::Pending
                                ? $approveAction->handle($record)
                                : null
                            );
                        }),

                    BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $rejectAction = resolve(RejectInteraction::class);
                            $records->each(fn (Interaction $record) => $record->status === ActivityStatus::Pending
                                ? $rejectAction->handle($record)
                                : null
                            );
                        }),
                ]),
            ]);
    }
}
