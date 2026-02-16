<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Models\EventSubmission;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventSubmissionSpeaker extends Pivot
{
    use HasFactory;

    protected $table = 'event_submission_speakers';

    protected $fillable = [
        'submission_id',
        'user_id',
    ];

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
