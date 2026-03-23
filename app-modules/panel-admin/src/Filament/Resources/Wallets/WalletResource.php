<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Wallets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Economy\Models\Wallet;
use He4rt\PanelAdmin\Filament\Resources\Wallets\Pages\ListWallets;
use He4rt\PanelAdmin\Filament\Resources\Wallets\RelationManagers\TransactionsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Wallets\Tables\WalletsTable;
use UnitEnum;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Economy';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return WalletsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
        ];
    }
}
