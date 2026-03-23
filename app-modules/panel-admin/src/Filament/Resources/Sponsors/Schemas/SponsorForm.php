<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SponsorForm
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
                TextInput::make('homepage_url')
                    ->required()
                    ->url(),
                SpatieMediaLibraryFileUpload::make('receipt_image')
                    ->collection('receipt')
                    ->disk('public'),
            ]);
    }
}
