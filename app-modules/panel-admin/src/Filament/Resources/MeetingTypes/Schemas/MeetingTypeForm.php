<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\MeetingTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MeetingTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('week_day')
                    ->required()
                    ->options([
                        0 => 'Domingo',
                        1 => 'Segunda',
                        2 => 'Terça',
                        3 => 'Quarta',
                        4 => 'Quinta',
                        5 => 'Sexta',
                        6 => 'Sábado',
                    ]),
                TextInput::make('start_at')
                    ->required()
                    ->integer()
                    ->helperText('Minutes from midnight (e.g., 1200 = 20:00)'),
            ]);
    }
}
