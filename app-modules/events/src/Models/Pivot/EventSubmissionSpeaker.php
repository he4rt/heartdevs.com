<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\EventSubmissionSpeakerFactory;
use He4rt\Events\Models\EventSubmission;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'submission_id',
    'user_id',
])]
#[Table(name: 'event_submission_speakers')]
class EventSubmissionSpeaker extends Pivot
{
    /** @use HasFactory<EventSubmissionSpeakerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EventSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EventSubmission::class, 'submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
