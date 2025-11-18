<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\AttendingStatusEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventAttend extends Pivot
{
    protected $table = 'events_attendees';

    protected $fillable = [
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendingStatusEnum::class,
        ];
    }
}
