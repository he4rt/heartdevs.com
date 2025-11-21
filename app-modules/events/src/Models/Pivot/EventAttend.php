<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Models\EventModel;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventAttend extends Pivot
{
    protected $table = 'events_attendees';

    protected $fillable = [
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (EventAttend $pivot): void {
            $event = EventModel::query()->find($pivot->event_id);

            match ($pivot->status) {
                AttendingStatusEnum::Attending => $event->increment('attendees_count'),
                AttendingStatusEnum::Waitlist => $event->increment('waitlist_count'),
                default => null,
            };
        });

        static::deleted(function (EventAttend $pivot): void {
            $event = EventModel::query()->find($pivot->event_id);

            match ($pivot->status) {
                AttendingStatusEnum::Attending => $event->decrement('attendees_count'),
                AttendingStatusEnum::Waitlist => $event->decrement('waitlist_count'),
                default => null,
            };
        });

        static::updated(function (EventAttend $pivot): void {
            if ($pivot->isDirty('status')) {
                $event = EventModel::query()->find($pivot->event_id);
                $oldStatus = AttendingStatusEnum::from($pivot->getOriginal('status'));
                $newStatus = $pivot->status;

                match ($oldStatus) {
                    AttendingStatusEnum::Attending => $event->decrement('attendees_count'),
                    AttendingStatusEnum::Waitlist => $event->decrement('waitlist_count'),
                    default => null,
                };

                match ($newStatus) {
                    AttendingStatusEnum::Attending => $event->increment('attendees_count'),
                    AttendingStatusEnum::Waitlist => $event->increment('waitlist_count'),
                    default => null,
                };
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => AttendingStatusEnum::class,
        ];
    }
}
