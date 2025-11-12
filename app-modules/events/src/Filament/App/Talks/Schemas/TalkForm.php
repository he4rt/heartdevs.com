<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\Talks\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use Illuminate\Database\Eloquent\Builder;

class TalkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->searchable()
                    ->relationship(
                        name: 'event',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', Filament::getTenant()->getKey())
                    )
                    ->required(),
                Hidden::make('user_id')
                    ->default(auth()->user()->getKey())
                    ->required(),
                Hidden::make('tenant_id')
                    ->default(Filament::getTenant()->getKey())
                    ->required(),
                Hidden::make('status')
                    ->default(TalkStatusEnum::Pending)
                    ->required(),
                TextInput::make('field_type')
                    ->label('Type')
                    ->minLength(3)
                    ->maxlength(255)
                    ->required(),
                TextInput::make('title')
                    ->label('Title')
                    ->minLength(3)
                    ->maxlength(255)
                    ->required(),
                RichEditor::make('description')
                    ->label('Description')
                    ->columnSpanFull()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
