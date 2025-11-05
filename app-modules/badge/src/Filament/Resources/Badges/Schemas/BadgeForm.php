<?php

declare(strict_types=1);

namespace He4rt\Badge\Filament\Resources\Badges\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use He4rt\Provider\Enums\ProviderEnum;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('provider')
                    ->enum(ProviderEnum::class)
                    ->options(ProviderEnum::class)
                    ->required(),
                TextInput::make('name')
                    ->minLength(3)
                    ->maxLength(255)
                    ->required(),
                RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('badge')
                    ->collection('badge')
                    ->image()
                    ->required(),
                TextInput::make('redeem_code')
                    ->required(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
