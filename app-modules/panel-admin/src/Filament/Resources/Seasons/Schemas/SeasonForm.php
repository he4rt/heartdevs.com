<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeasonForm
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

                DateTimePicker::make('started_at')
                    ->required(),

                DateTimePicker::make('ended_at')
                    ->nullable()
                    ->after('started_at'),
            ]);
    }
}
