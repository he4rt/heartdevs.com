<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Data;

use App\Data\Support\EnumOptionData;
use App\Support\Logging\EventLogLine;
use App\Support\Logging\WideEventKind;
use App\Support\Logging\WideEventOutcome;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

final class EventRowData extends Data
{
    public function __construct(
        public string $id,
        public string $day,
        public int $offset,
        public string $timestamp,
        public EnumOptionData $kind,
        public EnumOptionData $outcome,
        public string $title,
        public ?int $status,
        public ?float $durationMs,
        public ?string $keptBecause,
        public string $requestId,
        public ?string $userId,
    ) {}

    public static function fromLine(EventLogLine $line): self
    {
        $kind = WideEventKind::from(self::text($line, 'kind') ?? '');
        $duration = Arr::get($line->event, 'duration_ms');
        $status = Arr::get($line->event, 'http.status');

        return new self(
            id: sprintf('%s:%d', $line->day, $line->offset),
            day: $line->day,
            offset: $line->offset,
            timestamp: self::text($line, 'timestamp') ?? '',
            kind: EnumOptionData::fromEnum($kind),
            outcome: EnumOptionData::fromEnum(WideEventOutcome::from(self::text($line, 'outcome') ?? '')),
            title: self::title($kind, $line),
            status: is_int($status) ? $status : null,
            durationMs: is_int($duration) || is_float($duration) ? (float) $duration : null,
            keptBecause: self::text($line, 'sampling.kept_because'),
            requestId: self::text($line, 'request_id') ?? '',
            userId: self::text($line, 'user.id'),
        );
    }

    public static function text(EventLogLine $line, string $key): ?string
    {
        $value = Arr::get($line->event, $key);

        return is_string($value) ? $value : null;
    }

    private static function title(WideEventKind $kind, EventLogLine $line): string
    {
        return match ($kind) {
            WideEventKind::Http => mb_trim(sprintf('%s %s', self::text($line, 'http.method'), self::text($line, 'http.route') ?? self::text($line, 'http.uri') ?? self::text($line, 'http.path'))),
            WideEventKind::Job => class_basename(self::text($line, 'job.name') ?? 'Job'),
            WideEventKind::Command => self::text($line, 'command.name') ?? 'Command',
            WideEventKind::Schedule => self::text($line, 'schedule.task') ?? 'Scheduled task',
            WideEventKind::Mcp => self::text($line, 'mcp.tool') ?? 'MCP tool',
        };
    }
}
