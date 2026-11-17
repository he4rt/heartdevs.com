<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Queries;

use App\Support\Logging\EventLog;
use App\Support\Logging\EventLogLine;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\Logs\Data\EventActivityData;
use He4rt\PanelAdmin\Features\System\Logs\Data\EventDetailData;
use He4rt\PanelAdmin\Features\System\Logs\Data\EventFieldData;
use He4rt\PanelAdmin\Features\System\Logs\Data\EventRowData;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;

/**
 * One event with what shares its request id: the jobs of the request, or the request of a job, and the activity log entries.
 * The jobs of a request run soon after it, so the related events are read near its line only.
 */
final readonly class ShowEvent
{
    private const int RELATED_LIMIT = 20;

    public function __construct(private EventLog $log) {}

    public function handle(EventLogLine $line): EventDetailData
    {
        $requestId = EventRowData::text($line, 'request_id');
        $teamId = EventRowData::text($line, 'team.id');
        $errorType = EventRowData::text($line, 'error.type');

        return new EventDetailData(
            row: EventRowData::fromLine($line),
            userName: $this->userName(EventRowData::text($line, 'user.id')),
            impersonatorName: $this->userName(EventRowData::text($line, 'impersonator.id')),
            teamId: $teamId,
            teamName: $teamId === null ? null : $this->name(Team::query()->whereKey($teamId)->value('name')),
            errorType: $errorType,
            errorMessage: EventRowData::text($line, 'error.message'),
            frames: collect(Arr::wrap(Arr::get($line->event, 'error.frames')))->filter(fn (mixed $frame): bool => is_string($frame))->values()->all(),
            fields: collect(Arr::dot($line->event))
                ->map(fn (mixed $value, string|int $key): EventFieldData => new EventFieldData((string) $key, is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR)))
                ->values()
                ->all(),
            json: json_encode($line->event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
            related: $requestId === null ? [] : $this->related($line, $requestId),
            activities: $requestId === null ? [] : Activity::query()
                ->where('request_id', $requestId)
                ->latest('id')
                ->limit(self::RELATED_LIMIT)
                ->get()
                ->map(fn (Activity $activity): EventActivityData => EventActivityData::fromModel($activity))
                ->values()
                ->all(),
        );
    }

    /**
     * @return array<int, EventRowData>
     */
    private function related(EventLogLine $line, string $requestId): array
    {
        $sameRequest = fn (array $event): bool => ($event['request_id'] ?? null) === $requestId;
        $before = $this->log->readBackwards($line->day, $line->offset, self::RELATED_LIMIT, ListEvents::SCAN_BYTES, $sameRequest)->lines;
        $after = $this->log->readForward($line->day, $line->offset, self::RELATED_LIMIT, ListEvents::SCAN_BYTES, $sameRequest);

        return collect([...$before, ...$after])
            ->sortBy(fn (EventLogLine $related): int => $related->offset)
            ->map(fn (EventLogLine $related): EventRowData => EventRowData::fromLine($related))
            ->values()
            ->all();
    }

    private function userName(?string $id): ?string
    {
        return $id === null ? null : $this->name(User::query()->whereKey($id)->value('name'));
    }

    private function name(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
