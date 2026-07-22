<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\AvailableSources;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckConfigForm;

/**
 * CRUD editorial da retrospectiva (Fase 2): feio, mas cura e publica de verdade.
 * As linhas de deck_sources/deck_exclusions são campos auxiliares (não colunas):
 * as páginas expandem/recolhem o VO deck_config em torno deles.
 */
class RetrospectiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edição')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DateTimePicker::make('since')
                            ->label('Início do período')
                            ->seconds(condition: false)
                            ->timezone(config('app.display_timezone'))
                            ->required(),

                        DateTimePicker::make('until')
                            ->label('Fim do período')
                            ->seconds(condition: false)
                            ->timezone(config('app.display_timezone'))
                            ->required(),
                    ]),

                Section::make('Capa e fecho')
                    ->description('Só texto editorial; números, avatares e período são computados à parte.')
                    ->schema([
                        TextInput::make('cover_title')
                            ->label('Título da capa')
                            ->maxLength(255),

                        Textarea::make('cover_intro')
                            ->label('Introdução da capa')
                            ->rows(2),

                        Textarea::make('closing_text')
                            ->label('Mensagem de fecho')
                            ->rows(2),
                    ]),

                Section::make('Fontes e curadoria')
                    ->schema([
                        Toggle::make('hide_bots')
                            ->label('Ocultar bots')
                            ->default(state: true),

                        Repeater::make('deck_sources')
                            ->label('Fontes e ordem')
                            ->helperText('Arraste para ordenar os blocos. Desligue para ocultar a fonte do deck.')
                            ->addable(condition: false)
                            ->deletable(condition: false)
                            ->reorderable()
                            ->default(static fn (): array => DeckConfigForm::defaultSourceRows())
                            ->schema([
                                Hidden::make('key'),

                                TextInput::make('label')
                                    ->label('Fonte')
                                    ->disabled()
                                    ->dehydrated(condition: false),

                                Toggle::make('enabled')
                                    ->label('Exibir')
                                    ->default(state: true),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Repeater::make('deck_exclusions')
                            ->label('Exclusions')
                            ->helperText('Esconde um item ou pessoa do deck inteiro daquela fonte. Recompila o snapshot ao publicar.')
                            ->schema([
                                Select::make('source')
                                    ->label('Fonte')
                                    ->options(static fn (): array => AvailableSources::map())
                                    ->required(),

                                TextInput::make('ref')
                                    ->label('Ref')
                                    ->placeholder('pr:142, actor:login')
                                    ->required(),
                            ])
                            ->default([])
                            ->columns(2)
                            ->columnSpanFull()
                            ->addActionLabel('Adicionar exclusion'),
                    ]),
            ]);
    }
}
