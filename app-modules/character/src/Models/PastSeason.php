<?php

declare(strict_types=1);

namespace He4rt\Character\Models;

use He4rt\Character\Database\Factories\PastSeasonFactory;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Season\Models\Season;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PastSeason extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'seasons_rankings';

    protected $fillable = [
        'season_id',
        'character_id',
        'ranking_position',
        'experience',
        'messages_count',
        'badges_count',
        'meetings_count',
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
