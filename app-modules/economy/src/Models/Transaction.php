<?php

declare(strict_types=1);

namespace He4rt\Economy\Models;

use He4rt\Economy\Database\Factories\TransactionFactory;
use He4rt\Economy\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $wallet_id
 * @property TransactionType $type
 * @property int $amount
 * @property int $balance_after
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $description
 */
final class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
    ];

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }
}
