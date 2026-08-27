<?php

declare(strict_types=1);

namespace He4rt\Economy\Concerns;

use He4rt\Economy\Actions\CreateWallet;
use He4rt\Economy\Enums\Currency;
use He4rt\Economy\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWallet
{
    /**
     * @return MorphMany<Wallet, $this>
     */
    public function wallets(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'owner');
    }

    public function wallet(Currency $currency = Currency::Coin): ?Wallet
    {
        return $this->wallets()->where('currency', $currency)->first();
    }

    public function getOrCreateWallet(Currency $currency = Currency::Coin): Wallet
    {
        return resolve(CreateWallet::class)->handle($this, $currency);
    }
}
