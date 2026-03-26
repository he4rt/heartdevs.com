<?php

declare(strict_types=1);

namespace He4rt\Economy\Providers;

use He4rt\Economy\Shop\Models\ShopListing;
use He4rt\Economy\Trade\Models\Trade;
use He4rt\Economy\Trade\Models\TradeItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EconomyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Relation::morphMap([
            'shop_listing' => ShopListing::class,
            'trade' => Trade::class,
            'trade_item' => TradeItem::class,
        ]);
    }
}
