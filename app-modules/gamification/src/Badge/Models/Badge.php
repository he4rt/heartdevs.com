<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Models;

use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Database\Factories\BadgeFactory;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Badge extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'badges';

    protected $keyType = 'string';

    protected $fillable = [
        'provider',
        'name',
        'description',
        'redeem_code',
        'active',
        'tenant_id',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Character, $this, Pivot>
     */
    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(
            Character::class,
            'characters_badges',
            'badge_id',
            'character_id'
        )->withPivot(['claimed_at']);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('badge')
            ->useDisk('public');
    }

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'provider' => IdentityProvider::class,
        ];
    }
}
