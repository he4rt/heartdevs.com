<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Models;

use Carbon\Carbon;
use He4rt\Activity\Database\Factories\InteractionFactory;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $character_id
 * @property int $tenant_id
 * @property ActivityType $type
 * @property IdentityProvider $provider
 * @property ValueTier $value_tier
 * @property int $coins_min
 * @property int $coins_max
 * @property int|null $coins_awarded
 * @property int|null $xp_awarded
 * @property ActivityStatus $status
 * @property string|null $source_type
 * @property string|null $source_id
 * @property string|null $external_ref
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property Carbon|null $reviewed_at
 */
#[Fillable([
    'id',
    'character_id',
    'tenant_id',
    'type',
    'provider',
    'value_tier',
    'coins_min',
    'coins_max',
    'coins_awarded',
    'xp_awarded',
    'status',
    'source_type',
    'source_id',
    'external_ref',
    'metadata',
    'occurred_at',
    'reviewed_at',
])]
#[Table(name: 'interactions')]
final class Interaction extends Model
{
    /** @use HasFactory<InteractionFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): InteractionFactory
    {
        return InteractionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'provider' => IdentityProvider::class,
            'value_tier' => ValueTier::class,
            'status' => ActivityStatus::class,
            'coins_min' => 'integer',
            'coins_max' => 'integer',
            'coins_awarded' => 'integer',
            'xp_awarded' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
