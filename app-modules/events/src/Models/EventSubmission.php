<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\EventSubmissionFactory;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\Pivot\EventSubmissionSpeaker;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int|string $event_id
 * @property int|string $user_id
 * @property TalkStatusEnum $status
 * @property string $field_type
 * @property string $title
 * @property string $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
#[UseFactory(EventSubmissionFactory::class)]
class EventSubmission extends Model
{
    use HasFactory;

    protected $table = 'events_talks';

    protected $fillable = [
        'event_id',
        'user_id',
        'tenant_id',
        'status',
        'field_type',
        'title',
        'description',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<EventModel, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EventModel::class);
    }

    /**
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'event_submission_speakers',
            'submission_id',
            'user_id'
        )->using(EventSubmissionSpeaker::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function start(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->starts_at?->format('H:i'),
        );
    }

    protected function end(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ends_at?->format('H:i'),
        );
    }

    protected function casts(): array
    {
        return [
            'status' => TalkStatusEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
