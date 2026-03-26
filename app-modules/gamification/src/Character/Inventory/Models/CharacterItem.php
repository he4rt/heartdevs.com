<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Inventory\Models;

use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Database\Factories\CharacterItemFactory;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $character_id
 * @property string $item_id
 * @property int|null $tenant_id
 * @property AcquisitionMethod $acquired_via
 * @property Carbon $acquired_at
 */
final class CharacterItem extends Model
{
    /** @use HasFactory<CharacterItemFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'character_items';

    protected $fillable = [
        'id',
        'character_id',
        'item_id',
        'tenant_id',
        'acquired_via',
        'acquired_at',
    ];

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasOne<CharacterEquipment, $this>
     */
    public function equipment(): HasOne
    {
        return $this->hasOne(CharacterEquipment::class, 'character_item_id');
    }

    public function isEquipped(): bool
    {
        return $this->equipment()->exists();
    }

    protected static function newFactory(): CharacterItemFactory
    {
        return CharacterItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'acquired_via' => AcquisitionMethod::class,
            'acquired_at' => 'datetime',
        ];
    }
}
