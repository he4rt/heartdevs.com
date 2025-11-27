<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Query\Builder;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sponsors')
                    ->description('Sponsors Information')
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Tenant')
                            ->preload()
                            ->searchable()
                            ->relationship(
                                name: 'tenant',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                            )
                            ->required(),
                        TextInput::make('name')
                            ->minLength(5)
                            ->maxLength(255)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('receipt')
                            ->label('Sponsor Logo')
                            ->collection('receipt')
                            ->image()
                            ->required(),
                        TextInput::make('homepage_url')
                            ->url(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
