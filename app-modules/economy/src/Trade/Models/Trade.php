<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Models;

use He4rt\Economy\Database\Factories\TradeFactory;
use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $tenant_id
 * @property string $initiator_character_id
 * @property string $receiver_character_id
 * @property TradeStatus $status
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Trade extends Model
{
    /** @use HasFactory<TradeFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'initiator_character_id',
        'receiver_character_id',
        'status',
        'resolved_at',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'initiator_character_id');
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'receiver_character_id');
    }

    /**
     * @return HasMany<TradeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === TradeStatus::Pending;
    }

    protected static function newFactory(): TradeFactory
    {
        return TradeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'resolved_at' => 'datetime',
        ];
    }
}
