<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state)))
                            ->live(debounce: 500)
                            ->required(),
                        TextInput::make('domain')
                            ->required(),
                        TextInput::make('slug')
                            ->readonly()
                            ->partiallyRenderComponentsAfterStateUpdated(['name'])
                            ->required(),
                        Select::make('owner_id')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->relationship(
                                name: 'owner',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                            )
                            ->required(),
                        Toggle::make('active')
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
