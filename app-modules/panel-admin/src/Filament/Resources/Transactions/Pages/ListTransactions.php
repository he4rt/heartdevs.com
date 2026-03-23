<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Transactions\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Transactions\TransactionResource;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
