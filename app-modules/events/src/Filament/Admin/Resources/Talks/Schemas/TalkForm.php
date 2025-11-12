<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Talks\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;

class TalkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'username')
                    ->required(),
                Select::make('event_id')
                    ->label('EventModel')
                    ->relationship('event', 'title')
                    ->required(),
                TextInput::make('title')
                    ->label('Title')
                    ->minLength(3)
                    ->maxLength(255)
                    ->required(),
                Select::make('status')
                    ->enum(TalkStatusEnum::class)
                    ->options(TalkStatusEnum::class)
                    ->required(),
                TextInput::make('field_type')
                    ->maxLength(255)
                    ->required(),
                RichEditor::make('description')
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
            ]);
    }
}
