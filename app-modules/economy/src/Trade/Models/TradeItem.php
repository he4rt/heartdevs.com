<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Models;

use He4rt\Economy\Database\Factories\TradeItemFactory;
use He4rt\Economy\Trade\Enums\TradeDirection;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $trade_id
 * @property string $character_item_id
 * @property TradeDirection $direction
 * @property Carbon|null $created_at
 */
final class TradeItem extends Model
{
    /** @use HasFactory<TradeItemFactory> */
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'trade_id',
        'character_item_id',
        'direction',
    ];

    /**
     * @return BelongsTo<Trade, $this>
     */
    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * @return BelongsTo<CharacterItem, $this>
     */
    public function characterItem(): BelongsTo
    {
        return $this->belongsTo(CharacterItem::class);
    }

    protected static function newFactory(): TradeItemFactory
    {
        return TradeItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'direction' => TradeDirection::class,
        ];
    }
}
