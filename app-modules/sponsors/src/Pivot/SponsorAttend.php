<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Pivot;

use He4rt\Sponsors\Enums\SponsoringLevelEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SponsorAttend extends Pivot
{
    protected $table = 'sponsor_attend';

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
