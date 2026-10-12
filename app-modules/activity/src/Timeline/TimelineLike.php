<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline;

use Carbon\CarbonInterface;
use He4rt\Activity\Database\Factories\TimelineLikeFactory;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $timeline_id
 * @property string $user_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[UseFactory(factoryClass: TimelineLikeFactory::class)]
#[Table(name: 'activity_timeline_likes')]
final class TimelineLike extends Model
{
    /** @use HasFactory<TimelineLikeFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'timeline_id',
        'user_id',
    ];

    /** @return BelongsTo<Timeline, $this> */
    public function timeline(): BelongsTo
    {
        return $this->belongsTo(Timeline::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): TimelineLikeFactory
    {
        return TimelineLikeFactory::new();
    }
}
