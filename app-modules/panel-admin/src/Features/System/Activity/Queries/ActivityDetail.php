<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Queries;

use App\Support\Activitylog\ActivityChanges;
use App\Support\Activitylog\ActivityIntegrity;
use App\Support\Activitylog\ActivityRedactor;
use He4rt\PanelAdmin\Features\System\Activity\Data\ActivityChangeData;
use He4rt\PanelAdmin\Features\System\Activity\Data\ActivityDetailData;
use He4rt\PanelAdmin\Features\System\Activity\Data\ActivityRowData;
use Spatie\Activitylog\Models\Activity;

final readonly class ActivityDetail
{
    private const array CONTEXT_KEYS = ['ip_address', 'user_agent', 'request_id', 'method', 'path', 'route'];

    private const int RELATED_LIMIT = 20;

    public function __construct(private ActivityIntegrity $integrity) {}

    public function handle(Activity $activity): ActivityDetailData
    {
        $activity->loadMissing(['causer', 'subject']);

        return new ActivityDetailData(
            row: ActivityRowData::fromModel($activity),
            logName: $activity->log_name,
            subjectId: $activity->subject_id === null ? null : (string) $activity->subject_id,
            changes: $this->changes($activity),
            properties: ActivityRedactor::redact($activity->properties?->except([...self::CONTEXT_KEYS, 'attributes', 'old'])->all() ?? []),
            context: $this->context($activity),
            integrity: $this->integrityOf($activity),
            related: $this->related($activity),
        );
    }

    /** @return array<int, ActivityChangeData> */
    private function changes(Activity $activity): array
    {
        $old = ActivityRedactor::redact(ActivityChanges::old($activity));
        $new = ActivityRedactor::redact(ActivityChanges::new($activity));

        return collect([...array_keys($new), ...array_keys($old)])
            ->unique()
            ->map(fn (string|int $field): ActivityChangeData => new ActivityChangeData((string) $field, $old[$field] ?? null, $new[$field] ?? null))
            ->values()->all();
    }

    /** @return array<string, string|null> */
    private function context(Activity $activity): array
    {
        $stored = $activity->getAttributes();

        return collect(self::CONTEXT_KEYS)
            ->mapWithKeys(function (string $key) use ($activity, $stored): array {
                $value = $activity->properties?->get($key) ?? $stored[$key] ?? null;

                return [$key => is_scalar($value) ? (string) $value : null];
            })
            ->all();
    }

    /** @return 'valid'|'tampered'|'unsigned' */
    private function integrityOf(Activity $activity): string
    {
        $isSigned = filled($activity->getAttribute('integrity_hash'));

        return match (true) {
            !$isSigned => 'unsigned',
            $this->integrity->verify($activity) => 'valid',
            default => 'tampered',
        };
    }

    /** @return array<int, ActivityRowData> */
    private function related(Activity $activity): array
    {
        $requestId = $activity->getAttribute('request_id');

        if (!is_string($requestId) || $requestId === '') {
            return [];
        }

        return Activity::query()
            ->with(['causer', 'subject'])
            ->where('request_id', $requestId)
            ->whereKeyNot($activity->getKey())
            ->latest('id')
            ->limit(self::RELATED_LIMIT)
            ->get()
            ->map(ActivityRowData::fromModel(...))
            ->values()->all();
    }
}
