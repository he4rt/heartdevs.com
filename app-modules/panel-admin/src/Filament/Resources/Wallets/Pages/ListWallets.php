<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Wallets\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Wallets\WalletResource;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;
}
