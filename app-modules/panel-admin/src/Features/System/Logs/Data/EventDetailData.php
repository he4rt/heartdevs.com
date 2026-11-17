<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Data;

use Spatie\LaravelData\Data;

final class EventDetailData extends Data
{
    /**
     * @param  array<int, EventFieldData>  $fields
     * @param  array<int, string>  $frames
     * @param  array<int, EventRowData>  $related
     * @param  array<int, EventActivityData>  $activities
     */
    public function __construct(
        public EventRowData $row,
        public ?string $userName,
        public ?string $impersonatorName,
        public ?string $teamId,
        public ?string $teamName,
        public ?string $errorType,
        public ?string $errorMessage,
        public array $frames,
        public array $fields,
        public string $json,
        public array $related,
        public array $activities,
    ) {}
}
