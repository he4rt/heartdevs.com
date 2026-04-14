<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\AttendingStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property AttendingStatusEnum $status
 * @property int $attend_order
 */
#[Fillable([
    'status',
    'attend_order',
])]
#[Table(name: 'events_attendees')]
class EventAttend extends Pivot
{
    protected function casts(): array
    {
        return [
            'status' => AttendingStatusEnum::class,
        ];
    }
}
