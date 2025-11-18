<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\User\Filament\Admin\Resources\Users\Pages\CreateUser;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Filament\Admin\Resources\Users\Pages\ListUsers;
use He4rt\User\Filament\Admin\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\User\Filament\Admin\Resources\Users\Schemas\UserForm;
use He4rt\User\Filament\Admin\Resources\Users\Tables\UsersTable;
use He4rt\User\Models\User;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProvidersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
