<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\SponsoringLevelEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SponsorAttend extends Pivot
{
    protected $table = 'events_sponsors';

    protected $fillable = [
        'level',
    ];

    protected function casts(): array
    {
        return [
            'level' => SponsoringLevelEnum::class,
        ];
    }
}
