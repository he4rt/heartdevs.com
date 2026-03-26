<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Equipment\Models;

use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Database\Factories\CharacterEquipmentFactory;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $character_id
 * @property int $slot_id
 * @property string $character_item_id
 * @property int|null $tenant_id
 * @property Carbon $equipped_at
 */
final class CharacterEquipment extends Model
{
    /** @use HasFactory<CharacterEquipmentFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'character_equipment';

    protected $fillable = [
        'id',
        'character_id',
        'slot_id',
        'character_item_id',
        'tenant_id',
        'equipped_at',
    ];

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsTo<ItemSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(ItemSlot::class, 'slot_id');
    }

    /**
     * @return BelongsTo<CharacterItem, $this>
     */
    public function characterItem(): BelongsTo
    {
        return $this->belongsTo(CharacterItem::class, 'character_item_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function newFactory(): CharacterEquipmentFactory
    {
        return CharacterEquipmentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'equipped_at' => 'datetime',
        ];
    }
}
