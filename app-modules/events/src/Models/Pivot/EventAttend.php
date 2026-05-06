<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use Carbon\Carbon;
use He4rt\Events\Enums\AttendingStatusEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property AttendingStatusEnum $status
 * @property int $attend_order
 * @property Carbon|null $verified_at
 */
class EventAttend extends Pivot
{
    protected $table = 'events_attendees';

    protected $fillable = [
        'status',
        'attend_order',
        'verified_at',
    ];

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    protected function casts(): array
    {
        return [
            'status' => AttendingStatusEnum::class,
            'verified_at' => 'datetime',
        ];
    }
}
