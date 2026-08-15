<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use He4rt\Identity\User\Enums\Role;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use Illuminate\Support\Arr;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User')
                    ->tabs([
                        Tab::make('Identidade')
                            ->schema(self::identitySchema()),

                        Tab::make('Perfil')
                            ->schema([self::profileSection()]),

                        Tab::make('Endereço')
                            ->schema([self::addressSection()]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function identitySchema(): array
    {
        return [
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('name')
                ->label('Name')
                ->required(),

            TextInput::make('email')
                ->label('Email')
                ->email(),

            Select::make('role')
                ->options(Role::class)
                ->required()
                ->disabled(fn (): bool => !auth()->user()->role->isCompliance()),

            Toggle::make('is_donator')
                ->label('Donator'),
        ];
    }

    private static function profileSection(): Section
    {
        return Section::make('Perfil')
            ->relationship('profile')
            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                $preferences = $data['preferences'] instanceof WorkPreferences
                    ? $data['preferences']
                    : new WorkPreferences();

                return [
                    ...Arr::except($data, ['preferences']),
                    'has_disability' => $preferences->hasDisability,
                    'willing_to_relocate' => $preferences->willingToRelocate,
                    'is_open_to_remote' => $preferences->isOpenToRemote,
                    'employment_types' => array_map(
                        static fn (EmploymentType $type): string => $type->value,
                        $preferences->employmentTypes,
                    ),
                ];
            })
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [
                ...Arr::except($data, [
                    'has_disability',
                    'willing_to_relocate',
                    'is_open_to_remote',
                    'employment_types',
                ]),
                'preferences' => [
                    'has_disability' => $data['has_disability'] ?? false,
                    'willing_to_relocate' => $data['willing_to_relocate'] ?? false,
                    'is_open_to_remote' => $data['is_open_to_remote'] ?? false,
                    'employment_types' => $data['employment_types'] ?? [],
                ],
            ])
            ->schema([
                TextInput::make('nickname')
                    ->label('Nickname'),

                TextInput::make('headline')
                    ->label('Headline'),

                Textarea::make('about')
                    ->label('Sobre')
                    ->rows(3),

                DatePicker::make('birthdate')
                    ->label('Data de Nascimento'),

                Select::make('seniority_level')
                    ->label('Senioridade')
                    ->options(SeniorityLevel::class),

                TextInput::make('years_experience')
                    ->label('Anos de Experiência')
                    ->integer(),

                Toggle::make('available_for_proposals')
                    ->label('Disponível para Propostas'),

                Select::make('start_availability')
                    ->label('Disponibilidade de Início')
                    ->options(StartAvailability::class),

                TextInput::make('expected_salary_min')
                    ->label('Pretensão Salarial Mínima')
                    ->numeric()
                    ->prefix('R$'),

                TextInput::make('expected_salary_max')
                    ->label('Pretensão Salarial Máxima')
                    ->numeric()
                    ->prefix('R$'),

                TextInput::make('social_links.'.SocialPlatform::Instagram->value)
                    ->label('Instagram'),

                TextInput::make('social_links.'.SocialPlatform::Twitter->value)
                    ->label('Twitter'),

                TextInput::make('social_links.'.SocialPlatform::Website->value)
                    ->label('Website'),

                TextInput::make('social_links.'.SocialPlatform::YouTube->value)
                    ->label('YouTube'),

                TextInput::make('social_links.'.SocialPlatform::Bluesky->value)
                    ->label('Bluesky'),

                Toggle::make('has_disability')
                    ->label('Possui Deficiência'),

                Toggle::make('willing_to_relocate')
                    ->label('Disponível para Relocação'),

                Toggle::make('is_open_to_remote')
                    ->label('Aberto a Trabalho Remoto'),

                CheckboxList::make('employment_types')
                    ->label('Tipos de Contratação')
                    ->options(EmploymentType::class),
            ])
            ->columnSpanFull();
    }

    private static function addressSection(): Section
    {
        return Section::make('Endereço')
            ->relationship('address')
            ->schema([
                TextInput::make('country')
                    ->label('País')
                    ->maxLength(4),

                TextInput::make('state')
                    ->label('Estado'),

                TextInput::make('city')
                    ->label('Cidade'),

                TextInput::make('zip_code')
                    ->label('CEP')
                    ->mask('99999-999'),
            ])
            ->columnSpanFull();
    }
}
