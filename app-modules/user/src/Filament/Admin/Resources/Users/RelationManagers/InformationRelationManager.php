<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\User\Filament\Shared\Schemas\UserInformationForm;

class InformationRelationManager extends RelationManager
{
    protected static string $relationship = 'information';

    public function form(Schema $schema): Schema
    {
        return UserInformationForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('nickname')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('linkedin_url')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('github_url')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->label('Name')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
