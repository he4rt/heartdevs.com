<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Talks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;

class TalkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('event')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->required(),
                Select::make('status')
                    ->enum(TalkStatusEnum::class)
                    ->options([TalkStatusEnum::class])
                    ->required(),
                TextInput::make('field_type')
                    ->required(),
                Textarea::make('description')
                    ->autosize()
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
            ]);
    }
}
