<?php

declare(strict_types=1);

namespace He4rt\Season\Models;

use He4rt\Badge\Models\Badge;
use He4rt\Season\Database\Factories\SeasonFactory;
use Heart\Meeting\Infrastructure\Models\Meeting;
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

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    protected static function newFactory(): SeasonFactory
    {
        return SeasonFactory::new();
    }
}
