<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Data;

use Spatie\LaravelData\Data;

final class ActivityDetailData extends Data
{
    /**
     * @param  array<int, ActivityChangeData>  $changes
     * @param  array<array-key, mixed>  $properties
     * @param  array<string, string|null>  $context
     * @param  'valid'|'tampered'|'unsigned'  $integrity
     * @param  array<int, ActivityRowData>  $related
     */
    public function __construct(
        public ActivityRowData $row,
        public ?string $logName,
        public ?string $subjectId,
        public array $changes,
        public array $properties,
        public array $context,
        public string $integrity,
        public array $related,
    ) {}
}
