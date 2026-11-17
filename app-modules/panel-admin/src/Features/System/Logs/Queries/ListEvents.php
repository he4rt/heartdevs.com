<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Queries;

use App\Support\Logging\EventLog;
use App\Support\Logging\EventLogLine;
use App\Support\Logging\WideEventKind;
use App\Support\Logging\WideEventOutcome;
use Closure;
use He4rt\PanelAdmin\Features\System\Logs\Data\EventRowData;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * Reads one day of wide events, newest first. A filter that matches little stops after a byte budget
 * and hands back where to continue, so a large file never blocks the page.
 */
final readonly class ListEvents
{
    public const int PER_PAGE = 50;

    public const int SCAN_BYTES = 5_242_880;

    private const array SEARCHABLE_FIELDS = [
        'request_id', 'event_id', 'http.route', 'http.uri', 'http.path', 'job.name', 'command.name', 'schedule.task', 'mcp.tool', 'user.id', 'team.id',
    ];

    public function __construct(private EventLog $log) {}

    /**
     * @param  array<string, mixed>  $parameters  day, older_than, filter[search|kind|outcome|min_ms]
     * @return array{day: ?string, events: array<int, EventRowData>, olderThan: ?int}
     */
    public function handle(array $parameters): array
    {
        /** @var Fluent<string, mixed> $input */
        $input = new Fluent($parameters);
        $day = $input->filled('day') ? $input->string('day')->value() : Arr::first($this->days());

        if ($day === null) {
            return ['day' => null, 'events' => [], 'olderThan' => null];
        }

        $page = $this->log->readBackwards(
            $day,
            $input->filled('older_than') ? $input->integer('older_than') : null,
            self::PER_PAGE,
            self::SCAN_BYTES,
            $this->matcher($input),
        );

        return [
            'day' => $day,
            'events' => collect($page->lines)->map(fn (EventLogLine $line): EventRowData => EventRowData::fromLine($line))->values()->all(),
            'olderThan' => $page->olderThan,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function days(): array
    {
        return $this->log->days();
    }

    /**
     * @param  Fluent<string, mixed>  $input
     * @return Closure(array<array-key, mixed>): bool
     */
    private function matcher(Fluent $input): Closure
    {
        $search = Str::lower($input->string('filter.search')->trim()->value());
        $kind = $input->enum('filter.kind', WideEventKind::class);
        $outcome = $input->enum('filter.outcome', WideEventOutcome::class);
        $slowerThan = $input->filled('filter.min_ms') ? $input->integer('filter.min_ms') : null;

        return function (array $event) use ($search, $kind, $outcome, $slowerThan): bool {
            $duration = Arr::get($event, 'duration_ms');

            return ($kind === null || $event['kind'] === $kind->value)
                && ($outcome === null || $event['outcome'] === $outcome->value)
                && ($slowerThan === null || ((is_int($duration) || is_float($duration)) && $duration >= $slowerThan))
                && ($search === '' || $this->mentions($event, $search));
        };
    }

    /**
     * @param  array<array-key, mixed>  $event
     */
    private function mentions(array $event, string $search): bool
    {
        return collect(self::SEARCHABLE_FIELDS)->contains(function (string $field) use ($event, $search): bool {
            $value = Arr::get($event, $field);

            return is_string($value) && str_contains(Str::lower($value), $search);
        });
    }
}
