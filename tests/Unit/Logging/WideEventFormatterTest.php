<?php

declare(strict_types=1);

use App\Support\Logging\WideEventFormatter;
use Carbon\CarbonImmutable;
use Monolog\Level;
use Monolog\LogRecord;

it('writes the event alone, as one JSON line', function (): void {
    $record = new LogRecord(
        datetime: CarbonImmutable::now(),
        channel: 'events',
        level: Level::Info,
        message: 'http',
        context: ['kind' => 'http', 'http' => ['uri' => 'admin/teams/{team}'], 'duration_ms' => 12.0],
        extra: ['request_id' => 'abc'],
    );

    $line = (new WideEventFormatter)->format($record);

    expect($line)->toBe('{"kind":"http","http":{"uri":"admin/teams/{team}"},"duration_ms":12.0}'."\n")
        ->and((new WideEventFormatter)->formatBatch([$record, $record]))->toBe($line.$line);
});
