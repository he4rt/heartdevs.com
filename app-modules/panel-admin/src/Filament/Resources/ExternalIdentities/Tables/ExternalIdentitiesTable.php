<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExternalIdentitiesTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')
                    ->label('Tenant Id'),

                TextColumn::make('model_type')
                    ->label('Model Type'),

                TextColumn::make('model.name')
                    ->label('Owner')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph('model', '*', function (Builder $q) use ($search): void {
                        $q->where('name', 'ilike', sprintf('%%%s%%', $search));
                    })),

                TextColumn::make('type')
                    ->label('Type'),

                TextColumn::make('provider')
                    ->label('Provider'),

                TextColumn::make('external_account_id')
                    ->label('External Account Id'),

                TextColumn::make('connectedByUser.name')
                    ->label('Connected By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('connected_at')
                    ->label('Connected Date')
                    ->date(),

                TextColumn::make('disconnected_at')
                    ->label('Disconnected Date')
                    ->date(),

                TextColumn::make('metadata')
                    ->label('Metadata'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
