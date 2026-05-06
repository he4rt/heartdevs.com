<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Carbon\Carbon;
use Exception;
use He4rt\Events\Database\Factories\EventFactory;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\Pivot\EventAttend;
use He4rt\Events\Models\Pivot\SponsorAttend;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Date;

/**
 * @property bool $active
 * @property string $slug
 * @property EventTypeEnum $event_type
 * @property string $title
 * @property string $description
 * @property string $location
 * @property int $max_attendees
 * @property int $attendees_count
 * @property int $waitlist_count
 * @property int $tenant_id
 * @property Carbon $end_at
 * @property Carbon $start_at
 * @property Carbon $event_at
 */
#[UseFactory(EventFactory::class)]
class EventModel extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'active',
        'slug',
        'event_type',
        'title',
        'description',
        'event_at',
        'start_at',
        'end_at',
        'location',
        'max_attendees',
        'attendees_count',
        'waitlist_count',
        'tenant_id',
    ];

    /**
     * @return BelongsToMany<User, $this, EventAttend>
     */
    public function attendees(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                User::class,
                'events_attendees',
                'event_id',
                'user_id'
            )
            ->using(EventAttend::class)
            ->withPivot(['status', 'attend_order'])
            ->withTimestamps();
    }

    public function attend(mixed $userId, AttendingStatusEnum $status = AttendingStatusEnum::Attending): void
    {
        if ($this->isParticipating($userId)) {
            return;
        }

        match ($status) {
            AttendingStatusEnum::Attending => $this->increment('attendees_count'),
            AttendingStatusEnum::Waitlist => $this->increment('waitlist_count'),
            default => throw new Exception('Unexpected match value'),
        };

        $this->refresh();

        $this->attendees()->attach($userId, [
            'status' => $status,
            'attend_order' => $this->attendees_count + $this->waitlist_count,
        ]);
    }

    public function isPast(): bool
    {
        return $this->end_at < now();
    }

    public function leave(mixed $userId): bool
    {
        $eventAttend = $this->attendees()->where('user_id', $userId)->first();

        if (!$eventAttend) {
            return false;
        }

        /** @var EventAttend $pivot */
        $pivot = $eventAttend->pivot;
        match ($pivot->status) {
            AttendingStatusEnum::Attending => $this->decrement('attendees_count'),
            AttendingStatusEnum::Waitlist => $this->decrement('waitlist_count'),
            default => null,
        };

        $this->attendees()->detach($userId);

        return true;
    }

    public function isParticipating(mixed $userId): bool
    {
        return $this->attendees()->where('user_id', $userId)->exists();
    }

    public function isAttending(): bool
    {
        return $this->attendees()
            ->wherePivot('status', AttendingStatusEnum::Attending)
            ->exists();
    }

    public function onWaitlist(): bool
    {
        return $this->attendees()
            ->wherePivot('status', AttendingStatusEnum::Waitlist)
            ->exists();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<EventSubmission, $this>
     */
    public function talks(): HasMany
    {
        return $this->hasMany(EventSubmission::class, 'event_id');
    }

    /**
     * @return HasManyThrough<User, EventSubmission, $this>
     */
    public function speakers(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, EventSubmission::class, 'event_id', 'id', 'id', 'user_id');
    }

    /**
     * @return HasMany<EventAgenda, $this>
     */
    public function agenda(): HasMany
    {
        return $this->hasMany(EventAgenda::class, 'event_id')
            ->oldest('starting_at');
    }

    /**
     * @return BelongsToMany<Sponsor, $this, SponsorAttend>
     */
    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'events_sponsors', 'event_id', 'sponsor_id')
            ->using(SponsorAttend::class)
            ->withPivot(['level'])
            ->withTimestamps();
    }

    public function isVerificationWindowClosed(): bool
    {
        return Date::now()->greaterThanOrEqualTo(
            $this->end_at->copy()->addMinutes(30)
        );
    }

    public function verifyAttendance(int $userId): void
    {
        $this->attendees()->updateExistingPivot($userId, [
            'verified_at' => Date::now(),
        ]);
    }

    public function hasVerifiedAttendance(int $userId): bool
    {
        $attendee = $this->attendees()->where('user_id', $userId)->first();

        if (!$attendee) {
            return false;
        }

        return $attendee->pivot->verified_at !== null;
    }

    /** @return Attribute<int, never> */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_at->diffInHours($this->end_at),
        );
    }

    /** @return Attribute<string|null, never> */
    protected function start(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_at->format('H:i'),
        );
    }

    /** @return Attribute<string|null, never> */
    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->event_at->format('d/m'),
        );
    }

    /** @return Attribute<string|null, never> */
    protected function end(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_at->format('H:i'),
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function availableHours(Builder $query, string $start, string $end): Builder
    {
        $query->where('start_at', '<=', $start)
            ->where('end_at', '>=', $end);

        return $query->whereDoesntHave('talks', function (Builder $talkQuery) use ($start, $end): void {
            $talkQuery
                ->whereIn('status', [TalkStatusEnum::Accepted->value, TalkStatusEnum::Done->value]);

            $talkQuery->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start);
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'event_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'event_type' => EventTypeEnum::class,
        ];
    }
}
