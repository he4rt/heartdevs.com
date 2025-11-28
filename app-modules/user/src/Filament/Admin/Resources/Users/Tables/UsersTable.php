<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\User\Models\User;
use STS\FilamentImpersonate\Actions\Impersonate;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_donator')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Impersonate::make()
                    ->visible(fn (User $record) => (bool) $record->character?->tenant?->slug)
                    ->redirectTo(function (User $record): string {
                        $tenantName = $record->character?->tenant?->slug;

                        return '/app/'.$tenantName;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
