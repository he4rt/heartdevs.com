<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Models;

use Carbon\Carbon;
use He4rt\Gamification\Database\Factories\PastSeasonFactory;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $season_id
 * @property string $character_id
 * @property int $ranking_position
 * @property int $level
 * @property int $experience
 * @property int $messages_count
 * @property int $badges_count
 * @property int $meetings_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'seasons_rankings')]
final class PastSeason extends Model
{
    /** @use HasFactory<PastSeasonFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    protected static function newFactory(): PastSeasonFactory
    {
        return PastSeasonFactory::new();
    }
}
