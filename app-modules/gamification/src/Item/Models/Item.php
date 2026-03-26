<?php

declare(strict_types=1);

namespace He4rt\Gamification\Item\Models;

use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Database\Factories\ItemFactory;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int|null $tenant_id
 * @property int $slot_id
 * @property int $rarity_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_tradeable
 * @property bool $is_purchasable
 * @property int|null $price
 * @property float|null $drop_rate
 * @property int $level_required
 * @property bool $active
 * @property array<string, mixed>|null $metadata
 */
final class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'items';

    protected $fillable = [
        'id',
        'tenant_id',
        'slot_id',
        'rarity_id',
        'name',
        'slug',
        'description',
        'is_tradeable',
        'is_purchasable',
        'price',
        'drop_rate',
        'level_required',
        'active',
        'metadata',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<ItemSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(ItemSlot::class, 'slot_id');
    }

    /**
     * @return BelongsTo<ItemRarity, $this>
     */
    public function rarity(): BelongsTo
    {
        return $this->belongsTo(ItemRarity::class, 'rarity_id');
    }

    /**
     * @return HasMany<CharacterItem, $this>
     */
    public function characterItems(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_tradeable' => 'boolean',
            'is_purchasable' => 'boolean',
            'active' => 'boolean',
            'metadata' => 'array',
            'drop_rate' => 'decimal:4',
        ];
    }
}
