<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;
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
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->description('Quem tem super admin passa por cima de qualquer verificação de permissão.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('Papéis')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel())
                            ->descriptions(self::roleDescriptions(...))
                            ->disableOptionWhen(fn (int|string $value, ?User $record): bool => self::isOwnSuperAdminOption($value, $record) || self::isModeratorOption($value))
                            ->in(fn (CheckboxList $component): array => array_keys($component->getOptions()))
                            ->live()
                            ->afterStateUpdated(fn (mixed $state, mixed $old, Set $set) => $set('roles', self::keepOneDelasRole($state, $old)))
                            ->saveRelationshipsUsing(function (CheckboxList $component, ?User $record): void {
                                if ($record instanceof User && self::isEditingSelf($record)) {
                                    $component->state(self::keepOwnSuperAdmin($component->getState(), $record));
                                }

                                $component->state(self::keepModeratorAsIs($component->getState(), $record));

                                $roleIds = array_map(intval(...), self::keepOneDelasRole($component->getState(), old: null));

                                DB::transaction(fn (): ?User => $record?->syncRoles($roleIds));
                            })
                            ->helperText(fn (?User $record): ?string => self::isEditingSelf($record) ? 'Você não pode alterar o próprio super admin.' : null),
                    ]),
            ]);
    }

    private static function isEditingSelf(?User $record): bool
    {
        return $record?->is(auth()->user()) ?? false;
    }

    private static function isOwnSuperAdminOption(int|string $value, ?User $record): bool
    {
        return self::isEditingSelf($record) && (string) $value === self::superAdminRoleKey();
    }

    /**
     * @return array<int, string>
     */
    private static function keepOwnSuperAdmin(mixed $state, User $record): array
    {
        $superAdminKey = self::superAdminRoleKey();

        /** @var array<int, int|string> $submittedKeys */
        $submittedKeys = is_array($state) ? $state : [];

        $otherRoleKeys = array_values(array_filter(
            array_map(strval(...), $submittedKeys),
            fn (string $key): bool => $key !== $superAdminKey,
        ));

        if ($superAdminKey === null || !$record->isSuperAdmin()) {
            return $otherRoleKeys;
        }

        return [...$otherRoleKeys, $superAdminKey];
    }

    /**
     * Moderadora He4rt Delas só se ganha e se perde pela Equipe da He4rt Delas
     * (`AddDelasModerator` e `RemoveDelasModerator`), que conferem a tag e gravam o
     * histórico. Aqui a opção fica desabilitada e só aparece.
     */
    private static function isModeratorOption(int|string $value): bool
    {
        return (string) $value === self::roleKey(UserRole::DelasModerator);
    }

    /**
     * O form não dá nem tira o papel de moderadora: ele fica como está no banco.
     * Promover a líder continua possível, porque `keepOneDelasRole()` troca o papel.
     *
     * @return array<int, string>
     */
    private static function keepModeratorAsIs(mixed $state, ?User $record): array
    {
        $moderatorKey = self::roleKey(UserRole::DelasModerator);

        /** @var array<int, int|string> $submittedKeys */
        $submittedKeys = is_array($state) ? $state : [];

        $otherRoleKeys = array_values(array_filter(
            array_map(strval(...), $submittedKeys),
            fn (string $key): bool => $key !== $moderatorKey,
        ));

        if ($moderatorKey === null || !$record?->hasRole(UserRole::DelasModerator)) {
            return $otherRoleKeys;
        }

        return [...$otherRoleKeys, $moderatorKey];
    }

    private static function superAdminRoleKey(): ?string
    {
        $key = Role::query()
            ->where('name', UserRole::SuperAdmin->value)
            ->where('guard_name', UserRole::GUARD)
            ->value('id');

        return $key === null ? null : (string) $key;
    }

    /**
     * @return array<int, string>
     */
    private static function roleDescriptions(): array
    {
        return Role::query()
            ->where('guard_name', UserRole::GUARD)
            ->get()
            ->mapWithKeys(fn (Role $role): array => [(int) $role->getKey() => $role->name === UserRole::DelasModerator->value
                ? 'Gerencie pela Equipe da He4rt Delas.'
                : UserRole::from($role->name)->getDescription()])
            ->all();
    }

    /**
     * Líder e moderadora da He4rt Delas são exclusivas: a líder já faz tudo o
     * que a moderadora faz. Fica o papel marcado por último; sem essa
     * informação, fica a líder.
     *
     * @return array<int, string>
     */
    private static function keepOneDelasRole(mixed $state, mixed $old): array
    {
        /** @var array<int, int|string> $submittedKeys */
        $submittedKeys = is_array($state) ? $state : [];
        $keys = array_values(array_unique(array_map(strval(...), $submittedKeys)));

        $leadKey = self::roleKey(UserRole::DelasLead);
        $moderatorKey = self::roleKey(UserRole::DelasModerator);

        if (!in_array($leadKey, $keys, strict: true) || !in_array($moderatorKey, $keys, strict: true)) {
            return $keys;
        }

        /** @var array<int, int|string> $previousKeys */
        $previousKeys = is_array($old) ? $old : [];
        $previous = array_map(strval(...), $previousKeys);
        $moderatorJustChecked = in_array($leadKey, $previous, strict: true) && !in_array($moderatorKey, $previous, strict: true);

        $discarded = $moderatorJustChecked ? $leadKey : $moderatorKey;

        return array_values(array_filter($keys, fn (string $key): bool => $key !== $discarded));
    }

    private static function roleKey(UserRole $role): ?string
    {
        $key = Role::query()
            ->where('name', $role->value)
            ->where('guard_name', UserRole::GUARD)
            ->value('id');

        return $key === null ? null : (string) $key;
    }
}
