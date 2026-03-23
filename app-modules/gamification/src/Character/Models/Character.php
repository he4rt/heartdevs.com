<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Models;

use Carbon\Carbon;
use He4rt\Economy\Concerns\HasWallet;
use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Database\Factories\CharacterFactory;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Date;

/**
 * @property int $user_id
 * @property int $reputation
 * @property int $experience
 * @property Carbon|null $daily_bonus_claimed_at
 * @property int $level
 * @property float $percentage_experience
 * @property bool $can_claim_daily_bonus
 * @property int|null $tenant_id
 */
final class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;
    use HasUuids;
    use HasWallet;

    public const array LEVEL_THRESHOLDS = [
        1 => 0, 2 => 120, 3 => 250, 4 => 510, 5 => 1000,
        6 => 1500, 7 => 2100, 8 => 2800, 9 => 3600, 10 => 4500,
        11 => 5500, 12 => 6650, 13 => 7800, 14 => 9100, 15 => 10500,
        16 => 12000, 17 => 13700, 18 => 15500, 19 => 17500, 20 => 20000,
        21 => 23000, 22 => 26500, 23 => 30000, 24 => 34500, 25 => 39000,
        26 => 44000, 27 => 49500, 28 => 55500, 29 => 62000, 30 => 69000,
        31 => 77000, 32 => 85500, 33 => 95000, 34 => 105000, 35 => 116000,
        36 => 128000, 37 => 141000, 38 => 155000, 39 => 170000, 40 => 190000,
        41 => 210000, 42 => 230000, 43 => 250000, 44 => 270000, 45 => 290000,
        46 => 310000, 47 => 330000, 48 => 350000, 49 => 370000, 50 => 400000,
    ];

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

    public static function generateTextExperience(string $message, int $level, bool $isSupporter): int
    {
        $experience = (int) ((mb_strlen($message) * 0.01) + ($level * 0.1));

        return $isSupporter ? $experience * 2 : $experience;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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

    /**
     * @return Attribute<int, never>
     */
    protected function level(): Attribute
    {
        return Attribute::get(function (): int {
            $currentLevel = 1;

            foreach (self::LEVEL_THRESHOLDS as $level => $threshold) {
                if ($this->experience >= $threshold) {
                    $currentLevel = $level;
                }
            }

            return $currentLevel;
        });
    }

    /**
     * @return Attribute<int, never>
     */
    protected function experienceProgress(): Attribute
    {
        return Attribute::get(function (): int {
            $currentLevel = $this->level;
            $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel] ?? 0;
            $nextThreshold = self::LEVEL_THRESHOLDS[$currentLevel + 1] ?? $currentThreshold;

            return $nextThreshold - $this->experience;
        });
    }

    /**
     * @return Attribute<float, never>
     */
    protected function percentageExperience(): Attribute
    {
        return Attribute::get(function (): float {
            $currentLevel = $this->level;
            $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel] ?? 0;
            $nextThreshold = self::LEVEL_THRESHOLDS[$currentLevel + 1] ?? $currentThreshold;
            $range = $nextThreshold - $currentThreshold;

            if ($range <= 0) {
                return 100.0;
            }

            return round(($this->experience - $currentThreshold) / $range * 100, 2);
        });
    }

    /**
     * @return Attribute<float, never>
     */
    protected function experiencePercentageRemaining(): Attribute
    {
        return Attribute::get(fn (): float => round(100.0 - $this->percentage_experience, 2));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function canClaimDailyBonus(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->daily_bonus_claimed_at) {
                return true;
            }

            return Date::parse($this->daily_bonus_claimed_at)->diffInHours(now()) >= 24;
        });
    }
}
