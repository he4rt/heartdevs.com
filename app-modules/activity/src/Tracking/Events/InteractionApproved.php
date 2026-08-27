<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Events;

use He4rt\Activity\Tracking\Models\Interaction;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class InteractionApproved
{
    use Dispatchable;

    public function __construct(
        public Interaction $interaction,
    ) {}
}
