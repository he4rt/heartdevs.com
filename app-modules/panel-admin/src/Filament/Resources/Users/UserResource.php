<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\CreateUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\Schemas\UserForm;
use He4rt\PanelAdmin\Filament\Resources\Users\Tables\UsersTable;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            ProvidersRelationManager::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
