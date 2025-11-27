<?php

declare(strict_types=1);

namespace He4rt\Badge\Filament\Resources\Badges\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\Provider\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Builder;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Badge Information')
                    ->icon(Heroicon::Tag)
                    ->schema([
                        Section::make()
                            ->description('Administration Area')
                            ->schema([
                                Select::make('tenant_id')
                                    ->preload()
                                    ->searchable()
                                    ->relationship(
                                        name: 'tenant',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                                    )
                                    ->required(),
                                Select::make('provider')
                                    ->enum(ProviderEnum::class)
                                    ->options(ProviderEnum::class)
                                    ->required(),
                                Toggle::make('active')
                                    ->required(),
                                TextInput::make('redeem_code')
                                    ->required(),
                            ]),
                        TextInput::make('name')
                            ->minLength(3)
                            ->maxLength(255)
                            ->required(),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('badge')
                            ->collection('badge')
                            ->image()
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
