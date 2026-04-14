<?php

declare(strict_types=1);

namespace He4rt\Economy\Models;

use He4rt\Economy\Database\Factories\WalletFactory;
use He4rt\Economy\Enums\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $owner_type
 * @property string $owner_id
 * @property Currency $currency
 * @property int $balance
 */
#[Fillable([
    'owner_type',
    'owner_id',
    'currency',
    'balance',
])]
final class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'balance' => 'integer',
        ];
    }
}
