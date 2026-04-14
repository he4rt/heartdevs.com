<?php

declare(strict_types=1);

namespace He4rt\Events\Models\Pivot;

use He4rt\Events\Enums\SponsoringLevelEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'level',
])]
#[Table(name: 'events_sponsors')]
class SponsorAttend extends Pivot
{
    protected function casts(): array
    {
        return [
            'level' => SponsoringLevelEnum::class,
        ];
    }
}
