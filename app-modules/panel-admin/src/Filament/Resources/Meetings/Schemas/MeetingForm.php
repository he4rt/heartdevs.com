<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class MeetingForm
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
                Select::make('meeting_type_id')
                    ->required()
                    ->relationship('meetingType', 'name')
                    ->preload(),
                Select::make('admin_id')
                    ->required()
                    ->relationship('admin', 'username')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),
                RichEditor::make('content')
                    ->nullable(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->nullable(),
            ]);
    }
}
