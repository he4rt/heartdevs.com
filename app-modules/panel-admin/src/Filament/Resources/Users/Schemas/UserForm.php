<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                    ->description('Quem tem super admin passa por cima de qualquer verificação de permissão.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('Papéis')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel())
                            ->descriptions(self::roleDescriptions(...))
                            ->disableOptionWhen(self::isOwnSuperAdminOption(...))
                            ->in(fn (CheckboxList $component): array => array_keys($component->getOptions()))
                            ->saveRelationshipsUsing(function (CheckboxList $component, ?User $record): void {
                                if ($record instanceof User && self::isEditingSelf($record)) {
                                    $component->state(self::keepOwnSuperAdmin($component->getState(), $record));
                                }

                                $roleIds = array_map(intval(...), $component->getState() ?? []);

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
            ->mapWithKeys(fn (Role $role): array => [(int) $role->getKey() => UserRole::from($role->name)->getDescription()])
            ->all();
    }
}
