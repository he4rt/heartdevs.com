<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

class ExternalIdentitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Owner')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('metadata.username')
                    ->label('Platform User')
                    ->placeholder('-'),
                TextColumn::make('external_account_id')
                    ->copyable()
                    ->searchable()
                    ->label('External ID'),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->numeric()
                    ->counts('messages')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Status')
                    ->state(fn (ExternalIdentity $record): string => $record->isConnected() ? 'connected' : 'disconnected')
                    ->icon(fn (string $state): string => match ($state) {
                        'connected' => 'heroicon-o-check-circle',
                        'disconnected' => 'heroicon-o-x-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'connected' => 'success',
                        'disconnected' => 'danger',
                    }),
                TextColumn::make('connected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('connectedByUser.username')
                    ->label('Connected By')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options(IdentityProvider::class),
                SelectFilter::make('model_type')
                    ->label('Owner Type')
                    ->options([
                        User::class => 'User',
                        Tenant::class => 'Tenant',
                    ]),
                TernaryFilter::make('connected')
                    ->label('Connection Status')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('connected_at')->whereNull('disconnected_at'),
                        false: fn ($query) => $query->whereNotNull('disconnected_at'),
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
