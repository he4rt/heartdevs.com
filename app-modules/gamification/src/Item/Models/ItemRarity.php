<?php

declare(strict_types=1);

namespace He4rt\Gamification\Item\Models;

use He4rt\Gamification\Database\Factories\ItemRarityFactory;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property int $drop_weight
 */
final class ItemRarity extends Model
{
    /** @use HasFactory<ItemRarityFactory> */
    use HasFactory;

    protected $table = 'item_rarities';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'drop_weight',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'rarity_id');
    }

    protected static function newFactory(): ItemRarityFactory
    {
        return ItemRarityFactory::new();
    }
}
