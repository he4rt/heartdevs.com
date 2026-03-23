<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use Illuminate\Database\Eloquent\Builder;

class EventSubmissionForm
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
                    ->preload()
                    ->live(),
                Select::make('event_id')
                    ->required()
                    ->relationship('event', 'title', modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('tenant_id', $get('tenant_id')))
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->required()
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->minLength(3)
                    ->maxLength(255),
                TextInput::make('field_type')
                    ->required()
                    ->minLength(3)
                    ->maxLength(255),
                RichEditor::make('description')
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options(TalkStatusEnum::class)
                    ->default(TalkStatusEnum::Pending),
            ]);
    }
}
