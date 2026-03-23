<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Economy\Enums\Currency;
use He4rt\Economy\Models\Transaction;
use He4rt\Economy\Models\Wallet;

class EconomyStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Coin Balance', Wallet::query()->where('currency', Currency::Coin)->sum('balance'))
                ->icon(Heroicon::CurrencyDollar),
            Stat::make('Transactions Today', Transaction::query()->whereDate('created_at', today())->count())
                ->icon(Heroicon::ReceiptPercent),
        ];
    }
}
