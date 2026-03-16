<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Carbon\Traits\Date;
use Exception;
use He4rt\Events\Database\Factories\EventFactory;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\Pivot\EventAttend;
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
use Illuminate\Database\Eloquent\Relations\Pivot;

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
 * @property Date $end_at
 * @property Date $start_at
 * @property Date $event_at
 */
#[UseFactory(EventFactory::class)]
class EventModel extends Model
{
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
     * @return BelongsToMany<User, $this, Pivot>
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

        if (! $eventAttend) {
            return false;
        }

        match ($eventAttend->pivot->status) {
            AttendingStatusEnum::Attending => $this->decrement('attendees_count'),
            AttendingStatusEnum::Waitlist => $this->decrement('waitlist_count'),
            default => null,
        };

        $this->attendees()->detach($userId);

        return true;
    }

    public function isParticipating($userId): bool
    {
        return $this->attendees()->where('user_id', $userId)->exists();
    }

    public function isAttending(): bool
    {
        return $this->attendees->first()->pivot->status === AttendingStatusEnum::Attending;
    }

    public function onWaitlist(): bool
    {
        return $this->attendees->first()->pivot->status === AttendingStatusEnum::Waitlist;
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
     * @return HasManyThrough<User, EventSubmission>
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

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_at->diffInHours($this->end_at),
        );
    }

    protected function start(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_at?->format('H:i'),
        );
    }

    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->event_at?->format('d/m'),
        );
    }

    protected function end(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_at?->format('H:i'),
        );
    }

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

    #[Scope]
    protected function active($query)
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
