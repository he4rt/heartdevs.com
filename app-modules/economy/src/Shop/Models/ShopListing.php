<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\Models;

use He4rt\Economy\Database\Factories\ShopListingFactory;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $tenant_id
 * @property string $item_id
 * @property int $price
 * @property int|null $stock
 * @property Carbon|null $available_from
 * @property Carbon|null $available_until
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ShopListing extends Model
{
    /** @use HasFactory<ShopListingFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'item_id',
        'price',
        'stock',
        'available_from',
        'available_until',
        'active',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function isAvailable(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }

        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }

        if ($this->stock !== null && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): ShopListingFactory
    {
        return ShopListingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'active' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }
}
