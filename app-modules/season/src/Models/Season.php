<?php

declare(strict_types=1);

namespace He4rt\Season\Models;

use He4rt\Badge\Models\Badge;
use He4rt\Meeting\Models\Meeting;
use He4rt\Season\Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Season extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'seasons';

    protected $fillable = [
        'id',
        'name',
        'description',
        'messages_count',
        'participants_count',
        'meeting_count',
        'badges_count',
        'started_at',
        'ended_at',
    ];

    /**
     * @return HasMany<Badge, $this>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    /**
     * @return HasMany<Meeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    protected static function newFactory(): SeasonFactory
    {
        return SeasonFactory::new();
    }
}
