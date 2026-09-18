<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Identificação')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->unique(),

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->unique(),
                    ]),

                Section::make('Sinalizações')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_donator')
                            ->label('Apoiador')
                            ->helperText('Marca manual de apoiador. Não afeta permissões.'),
                    ]),

                Section::make('Papéis')
                    ->columnSpanFull()
                    ->columns(1)
                    ->description('Quem tem super admin passa por cima de qualquer verificação de permissão.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('Papéis')
                            ->relationship(
                                'roles',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->whereIn('name', self::assignableRoleNames()),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel())
                            ->descriptions(self::roleDescriptions(...))
                            ->disabled(self::isEditingSelf(...))
                            ->helperText(fn (?User $record): ?string => self::isEditingSelf($record) ? 'Você não pode alterar os próprios papéis.' : null)
                            ->saveRelationshipsUsing(static function (User $record, ?array $state): void {
                                $assignable = self::assignableRoleNames();

                                $keptRoleIds = $record->roles()
                                    ->whereNotIn('name', $assignable)
                                    ->pluck('roles.id');

                                $selectedRoleIds = Role::query()
                                    ->where('guard_name', UserRole::GUARD)
                                    ->whereIn('name', $assignable)
                                    ->whereIn('id', $state ?? [])
                                    ->pluck('id');

                                $record->roles()->sync($keptRoleIds->merge($selectedRoleIds));
                            }),
                    ]),

                Section::make('Perfil')
                    ->columnSpanFull()
                    ->columns(2)
                    ->relationship('profile')
                    ->mutateRelationshipDataBeforeFillUsing(static function (array $data): array {
                        $preferences = $data['preferences'] ?? null;
                        $data['preferences'] = $preferences instanceof WorkPreferences ? $preferences->toArray() : $preferences;

                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(static function (array $data): array {
                        $preferences = (array) ($data['preferences'] ?? []);
                        $data['preferences'] = WorkPreferences::makeFromPayload($preferences);

                        return $data;
                    })
                    ->schema([
                        TextInput::make('nickname')
                            ->label('Apelido')
                            ->maxLength(255),

                        TextInput::make('headline')
                            ->label('Headline')
                            ->maxLength(255),

                        Textarea::make('about')
                            ->label('Sobre')
                            ->columnSpanFull(),

                        DatePicker::make('birthdate')
                            ->label('Data de nascimento'),

                        Select::make('seniority_level')
                            ->label('Senioridade')
                            ->options(SeniorityLevel::class),

                        TextInput::make('years_experience')
                            ->label('Anos de experiência')
                            ->integer()
                            ->minValue(0),

                        Toggle::make('available_for_proposals')
                            ->label('Aberto a propostas'),

                        Select::make('start_availability')
                            ->label('Disponibilidade')
                            ->options(StartAvailability::class),

                        TextInput::make('expected_salary_min')
                            ->label('Pretensão salarial (mín.)')
                            ->numeric(),

                        TextInput::make('expected_salary_max')
                            ->label('Pretensão salarial (máx.)')
                            ->numeric(),

                        KeyValue::make('social_links')
                            ->label('Redes sociais')
                            ->keyLabel('Plataforma')
                            ->valueLabel('Handle/URL')
                            ->columnSpanFull()
                            ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                                $invalidPlatforms = array_diff(array_keys((array) $value), SocialPlatform::values());

                                if ($invalidPlatforms !== []) {
                                    $fail(sprintf('Invalid social platform keys: %s.', implode(', ', $invalidPlatforms)));
                                }
                            }),

                        Toggle::make('preferences.has_disability')
                            ->label('Possui deficiência'),

                        Toggle::make('preferences.willing_to_relocate')
                            ->label('Aberto a relocação'),

                        Toggle::make('preferences.is_open_to_remote')
                            ->label('Aberto a remoto'),

                        CheckboxList::make('preferences.employment_types')
                            ->label('Tipos de contrato aceitos')
                            ->options(EmploymentType::class)
                            ->columnSpanFull(),
                    ]),

                Section::make('Endereço')
                    ->columnSpanFull()
                    ->columns(3)
                    ->relationship('address')
                    ->schema([
                        TextInput::make('country')
                            ->label('País')
                            ->maxLength(255),

                        TextInput::make('state')
                            ->label('Estado')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(255),
                    ]),
            ]);
    }

    private static function isEditingSelf(?User $record): bool
    {
        return $record?->is(auth()->user()) ?? false;
    }

    /**
     * @return array<int, string>
     */
    private static function assignableRoleNames(): array
    {
        return array_map(
            static fn (UserRole $role): string => $role->value,
            auth()->user()?->assignableRoles() ?? [],
        );
    }

    /**
     * @return array<int, string>
     */
    private static function roleDescriptions(): array
    {
        return Role::query()
            ->where('guard_name', UserRole::GUARD)
            ->get()
            ->mapWithKeys(fn (Role $role): array => [(int) $role->getKey() => UserRole::from($role->name)->getDescription()])
            ->all();
    }
}
