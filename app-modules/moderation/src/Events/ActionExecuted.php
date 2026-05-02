<?php

declare(strict_types=1);

namespace He4rt\Moderation\Events;

use He4rt\Moderation\Models\ModerationAction;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ActionExecuted
{
    use Dispatchable;

    public function __construct(public ModerationAction $action) {}
}
