<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Enums\CheckinStatusEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property AttendingStatusEnum $status
 * @property int $attend_order
 */
class EventAttend extends Pivot
{
    protected $table = 'events_attendees';

    protected $fillable = [
        'status',
        'attend_order',
        'state',
        'verified_at',
        'verification_method',
        'xp_awarded',
        'streak_multiplier',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendingStatusEnum::class,
            'state' => CheckinStatusEnum::class,
        ];
    }
}
