<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Badges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('tenant_id')
                    ->required()
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->nullable()
                    ->rows(3),

                TextInput::make('redeem_code')
                    ->required(),

                Select::make('provider')
                    ->required()
                    ->options(IdentityProvider::class),

                Toggle::make('active')
                    ->default(true),

                SpatieMediaLibraryFileUpload::make('badge_image')
                    ->collection('badge')
                    ->disk('public'),
            ]);
    }
}
