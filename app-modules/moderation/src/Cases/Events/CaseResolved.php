<?php

declare(strict_types=1);

namespace He4rt\Moderation\Cases\Events;

use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CaseResolved
{
    use Dispatchable;

    public function __construct(public ModerationCase $case) {}
}
