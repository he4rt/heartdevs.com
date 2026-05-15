<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;

final class XpCalculatorAction
{
    public function execute(EventModel $eventModel): int
    {
        // Fixo até HE4-47

        return $eventModel->xp_base * 1;
    }
}
