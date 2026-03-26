<?php

declare(strict_types=1);

namespace He4rt\Gamification\Item\Models;

use He4rt\Gamification\Database\Factories\ItemSlotFactory;
use He4rt\Gamification\Item\Enums\SlotType;
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
 * @property SlotType $slot_type
 * @property int $display_order
 */
final class ItemSlot extends Model
{
    /** @use HasFactory<ItemSlotFactory> */
    use HasFactory;

    protected $table = 'item_slots';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'slot_type',
        'display_order',
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
        return $this->hasMany(Item::class, 'slot_id');
    }

    protected static function newFactory(): ItemSlotFactory
    {
        return ItemSlotFactory::new();
    }

    protected function casts(): array
    {
        return [
            'slot_type' => SlotType::class,
        ];
    }
}
