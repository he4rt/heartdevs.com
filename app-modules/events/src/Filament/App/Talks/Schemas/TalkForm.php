<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\Talks\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Filament\Shared\Schemas\StartEndFieldsSchema;
use Illuminate\Database\Eloquent\Builder;

class TalkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
                    Section::make('Proposta da Palestra')
                        ->description('Defina o evento, título e tipo da sua proposta.')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->schema([
                            Select::make('event_id')
                                ->label('Evento')
                                ->preload()
                                ->searchable()
                                ->relationship(
                                    name: 'event',
                                    titleAttribute: 'title',
                                    modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', Filament::getTenant()->getKey())
                                )
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('field_type')
                                ->label('Tipo')
                                ->minLength(3)
                                ->maxlength(255)
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('title')
                                ->label('Título da Proposta')
                                ->minLength(3)
                                ->maxlength(255)
                                ->required()
                                ->columnSpanFull(),
                            Section::make('Horários da Palestra')
                                ->description('Forneça os horários da sua palestra')
                                ->schema(
                                    StartEndFieldsSchema::make(),
                                ),
                        ])->columnSpan(3),

                ])
                    ->columnSpanFull(),

                Section::make('Detalhes e Conteúdo')
                    ->description('Forneça a descrição completa da sua palestra e o que o público aprenderá.')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        RichEditor::make('description')
                            ->label('Descrição Completa')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Hidden::make('user_id')
                    ->default(auth()->user()->getKey())
                    ->required(),
                Hidden::make('tenant_id')
                    ->default(Filament::getTenant()->getKey())
                    ->required(),
                Hidden::make('status')
                    ->default(TalkStatusEnum::Pending)
                    ->required(),
            ]);
    }
}
