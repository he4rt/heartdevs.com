<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Models\Pivot;

use He4rt\Sponsors\Enums\SponsoringLevelEnum;
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
