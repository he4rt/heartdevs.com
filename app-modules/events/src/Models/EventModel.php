<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Exception;
use He4rt\Events\Database\Factories\EventFactory;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\Pivot\EventAttend;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
            ->withPivot('status')
            ->withTimestamps();
    }

    public function attend(mixed $userId, AttendingStatusEnum $status = AttendingStatusEnum::Attending): bool
    {

        $this->attendees()->attach($userId, ['status' => $status]);

        match ($status) {
            AttendingStatusEnum::Attending => $this->increment('attendees_count'),
            AttendingStatusEnum::Waitlist => $this->increment('waitlist_count'),
            default => throw new Exception('Unexpected match value'),
        };

        return true;
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

    public function participate($userId): bool
    {
        return $this->attendees()->where('user_id', $userId)->exists();
    }

    public function isAttending(): bool
    {
        return $this->attendees()->first()->pivot->status === AttendingStatusEnum::Attending;
    }

    public function onWaitlist(): bool
    {
        return $this->attendees()->first()->pivot->status === AttendingStatusEnum::Waitlist;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
