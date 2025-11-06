<?php

declare(strict_types=1);

namespace He4rt\Character\Models;

use Carbon\Carbon;
use He4rt\Badge\Models\Badge;
use He4rt\Character\Database\Factories\CharacterFactory;
use He4rt\Character\Entities\LevelEntity;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $user_id
 * @property int reputation
 * @property int $experience
 * @property Carbon $daily_bonus_claimed_at
 */
final class Character extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'characters';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'reputation',
        'experience',
        'daily_bonus_claimed_at',
    ];

    protected $appends = [
        'ranking',
        'level',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return BelongsToMany<Badge, $this, Pivot>
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'characters_badges',
            'character_id',
            'badge_id'
        )->withPivot(['claimed_at']);
    }

    /**
     * @return HasMany<PastSeason, $this>
     */
    public function pastSeasons(): HasMany
    {
        return $this->hasMany(PastSeason::class);
    }

    protected static function newFactory(): CharacterFactory
    {
        return CharacterFactory::new();
    }

    protected function getRankingAttribute(): int
    {
        return $this->newQuery()
            ->orderByDesc('experience')
            ->pluck('id')
            ->filter(fn ($id) => $id === $this->getKey())
            ->keys()
            ->first() + 1;
    }

    protected function getLevelAttribute(): int
    {
        return (new LevelEntity($this->experience))->getLevel();
    }
}
