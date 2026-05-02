<?php

declare(strict_types=1);

namespace He4rt\Moderation\Events;

use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CaseResolved
{
    use Dispatchable;

    public function __construct(public ModerationCase $case) {}
}
