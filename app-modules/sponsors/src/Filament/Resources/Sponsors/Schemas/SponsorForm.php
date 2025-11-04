<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
                FileUpload::make('logo_path')
                    ->required(),
                TextInput::make('homepage_url'),
            ]);
    }
}
