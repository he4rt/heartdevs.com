<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\User\Filament\Shared\Schemas\UserAddressForm;

class AddressRelationManager extends RelationManager
{
    protected static string $relationship = 'address';

    public function form(Schema $schema): Schema
    {
        return UserAddressForm::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country')
                    ->badge()
                    ->searchable(),
                TextColumn::make('state')
                    ->badge()
                    ->searchable(),
                TextColumn::make('city')
                    ->badge()
                    ->searchable(),
                TextColumn::make('zip_code')
                    ->badge()
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
