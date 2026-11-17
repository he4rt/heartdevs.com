<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Closure;

/**
 * Reads the JSONL files that the `events_daily` channel writes, one per day.
 * A read never loads a whole file: it walks the file in blocks and stops after a byte budget.
 */
final readonly class EventLog
{
    private const int BLOCK_BYTES = 65_536;

    /**
     * @return array<int, string> the days that have a file, newest first, as Y-m-d
     */
    public function days(): array
    {
        $pattern = sprintf('/^%s-(\d{4}-\d{2}-\d{2})\.%s$/', preg_quote($this->baseName(), '/'), preg_quote($this->extension(), '/'));

        return collect(glob($this->directory().DIRECTORY_SEPARATOR.$this->baseName().'-*.'.$this->extension()) ?: [])
            ->map(fn (string $path): ?string => preg_match($pattern, basename($path), $matches) === 1 ? $matches[1] : null)
            ->filter()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Reads newest first, from the end of the file or from `$olderThan`.
     *
     * @param  Closure(array<array-key, mixed>): bool  $matches
     */
    public function readBackwards(string $day, ?int $olderThan, int $limit, int $maxBytes, Closure $matches): EventLogPage
    {
        $handle = $this->open($day);

        if ($handle === null) {
            return new EventLogPage([], olderThan: null);
        }

        $position = min($olderThan ?? PHP_INT_MAX, $this->size($day));
        $carry = '';
        $scanned = 0;
        $found = [];

        while ($position > 0) {
            $read = min(self::BLOCK_BYTES, $position);
            $position -= $read;
            fseek($handle, $position);
            $carry = fread($handle, $read).$carry;
            $scanned += $read;

            $segments = explode("\n", $carry);
            $partial = $position > 0 ? array_shift($segments) : '';
            $firstOffset = $position + ($position > 0 ? mb_strlen($partial, '8bit') + 1 : 0);

            foreach (collect($this->linesOf($segments, $firstOffset))->reverse() as [$offset, $text]) {
                $event = $this->decode($text);

                if ($event !== null && $matches($event)) {
                    $found[] = new EventLogLine($day, $offset, $event);
                }

                if (count($found) === $limit) {
                    fclose($handle);

                    return new EventLogPage($found, $offset > 0 ? $offset : null);
                }
            }

            $carry = $partial;

            if ($scanned >= $maxBytes && $position > 0) {
                fclose($handle);

                return new EventLogPage($found, $firstOffset);
            }
        }

        fclose($handle);

        return new EventLogPage($found, olderThan: null);
    }

    /**
     * The event whose line starts exactly at `$offset`, or null.
     */
    public function lineAt(string $day, int $offset): ?EventLogLine
    {
        $handle = $this->open($day);

        if ($handle === null || $offset >= $this->size($day)) {
            return null;
        }

        $startsALine = $offset === 0 || (fseek($handle, $offset - 1) === 0 && fread($handle, 1) === "\n");
        $text = $startsALine && fseek($handle, $offset) === 0 ? fgets($handle) : false;
        fclose($handle);

        $event = is_string($text) ? $this->decode($text) : null;

        return $event === null ? null : new EventLogLine($day, $offset, $event);
    }

    /**
     * The events after `$offset` that match, read forward up to `$maxBytes`.
     *
     * @param  Closure(array<array-key, mixed>): bool  $matches
     * @return array<int, EventLogLine>
     */
    public function readForward(string $day, int $offset, int $limit, int $maxBytes, Closure $matches): array
    {
        $handle = $this->open($day);

        if ($handle === null) {
            return [];
        }

        fseek($handle, $offset);
        fgets($handle);
        $found = [];

        while (count($found) < $limit && ftell($handle) - $offset < $maxBytes) {
            $lineOffset = (int) ftell($handle);
            $text = fgets($handle);

            if ($text === false) {
                break;
            }

            $event = $this->decode($text);

            if ($event !== null && $matches($event)) {
                $found[] = new EventLogLine($day, $lineOffset, $event);
            }
        }

        fclose($handle);

        return $found;
    }

    /**
     * The offset and text of every non-empty line, oldest first.
     *
     * @param  array<int, string>  $segments
     * @return array<int, array{0: int, 1: string}>
     */
    private function linesOf(array $segments, int $firstOffset): array
    {
        $lines = [];
        $offset = $firstOffset;

        foreach ($segments as $segment) {
            if (mb_trim($segment) !== '') {
                $lines[] = [$offset, $segment];
            }

            $offset += mb_strlen($segment, '8bit') + 1;
        }

        return $lines;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function decode(string $text): ?array
    {
        $event = json_decode($text, associative: true);

        if (!is_array($event)) {
            return null;
        }

        $isAnEvent = WideEventKind::tryFrom(is_string($event['kind'] ?? null) ? $event['kind'] : '') instanceof WideEventKind
            && WideEventOutcome::tryFrom(is_string($event['outcome'] ?? null) ? $event['outcome'] : '') instanceof WideEventOutcome;

        return $isAnEvent ? $event : null;
    }

    /**
     * @return resource|null
     */
    private function open(string $day)
    {
        $path = $this->path($day);

        if (!is_file($path)) {
            return null;
        }

        $handle = fopen($path, 'rb');

        return $handle === false ? null : $handle;
    }

    private function size(string $day): int
    {
        clearstatcache(clear_realpath_cache: true, filename: $this->path($day));

        return (int) filesize($this->path($day));
    }

    private function path(string $day): string
    {
        return sprintf('%s%s%s-%s.%s', $this->directory(), DIRECTORY_SEPARATOR, $this->baseName(), $day, $this->extension());
    }

    private function directory(): string
    {
        return dirname($this->configuredPath());
    }

    private function baseName(): string
    {
        return pathinfo($this->configuredPath(), PATHINFO_FILENAME);
    }

    private function extension(): string
    {
        return pathinfo($this->configuredPath(), PATHINFO_EXTENSION);
    }

    private function configuredPath(): string
    {
        return config()->string('logging.channels.events_daily.path');
    }
}
