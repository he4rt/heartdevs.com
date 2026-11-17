<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Data;

use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

final class EventActivityData extends Data
{
    public function __construct(
        public int $id,
        public string $description,
        public ?string $event,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        return new self(
            id: (int) $activity->getKey(),
            description: $activity->description,
            event: $activity->event,
        );
    }
}
