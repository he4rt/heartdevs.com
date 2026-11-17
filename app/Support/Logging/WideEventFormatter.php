<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Writes the event alone, as one JSON line. The level, the message and the Context extras stay out.
 */
final class WideEventFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        return json_encode($record->context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @param  array<int, LogRecord>  $records
     */
    public function formatBatch(array $records): string
    {
        return collect($records)->map(fn (LogRecord $record): string => $this->format($record))->implode('');
    }
}
