<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Provider\Models\Provider;

class ProvidersRelationManager extends RelationManager
{
    protected static string $relationship = 'providers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('provider')
                    ->required(),

                TextInput::make('provider_id')
                    ->required(),

                TextInput::make('email'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn (?Provider $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn (?Provider $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                TextInput::make('model_type'),

                TextInput::make('user_id')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('provider'),

                TextColumn::make('provider_id'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_type'),

                TextColumn::make('user_id'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
