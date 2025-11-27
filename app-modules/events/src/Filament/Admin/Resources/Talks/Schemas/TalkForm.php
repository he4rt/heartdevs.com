<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Talks\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\Talks\TalkStatusEnum;

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
                            TextInput::make('title')
                                ->label('Título da Proposta')
                                ->minLength(3)
                                ->maxlength(255)
                                ->required()
                                ->columnSpanFull(),

                            Select::make('tenant_id')
                                ->label('Tenant')
                                ->relationship('tenant', 'name')
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            Select::make('user_id')
                                ->label('User')
                                ->relationship('user', 'username')
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('field_type')
                                ->label('Tipo')
                                ->minLength(3)
                                ->maxlength(255)
                                ->required()
                                ->columnSpan(1),

                            Select::make('status')
                                ->label('Status')
                                ->options(TalkStatusEnum::class)
                                ->required()
                                ->default(TalkStatusEnum::Pending),
                        ])
                        ->columnSpan(2),
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

            ]);
    }
}
