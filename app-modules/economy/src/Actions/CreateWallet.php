<?php

declare(strict_types=1);

namespace He4rt\Economy\Actions;

use He4rt\Economy\Enums\Currency;
use He4rt\Economy\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

final class CreateWallet
{
    public function handle(Model $owner, Currency $currency = Currency::Coin): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
                'currency' => $currency,
            ],
            ['balance' => 0]
        );
    }
}
