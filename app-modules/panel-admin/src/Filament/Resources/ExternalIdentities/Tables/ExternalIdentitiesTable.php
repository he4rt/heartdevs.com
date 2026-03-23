<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class ExternalIdentitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->badge(),
                TextColumn::make('model_type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                TextColumn::make('external_account_id')
                    ->copyable()
                    ->label('External ID'),
                TextColumn::make('credentials_type')
                    ->badge(),
                TextColumn::make('connected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('disconnected_at')
                    ->dateTime()
                    ->placeholder('Active'),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options(IdentityProvider::class),
                TrashedFilter::make(),
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
