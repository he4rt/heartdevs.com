<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 * The `error.*` fields of a wide event. Only the app frames go in, and a query error goes in without its bindings.
 */
final class ExceptionFields
{
    private const int MAX_FRAMES = 10;

    /**
     * @return array<string, scalar|null>
     */
    public static function of(Throwable $exception): array
    {
        return [
            'type' => $exception::class,
            'message' => self::message($exception),
            'code' => $exception->getCode(),
            ...self::frames($exception),
            ...self::context($exception),
        ];
    }

    private static function message(Throwable $exception): string
    {
        if ($exception instanceof QueryException) {
            return sprintf('SQLSTATE[%s] %s', $exception->getCode(), $exception->getSql());
        }

        return $exception->getMessage();
    }

    /**
     * @return array<string, string>
     */
    private static function frames(Throwable $exception): array
    {
        $vendor = base_path('vendor').DIRECTORY_SEPARATOR;

        return collect([['file' => $exception->getFile(), 'line' => $exception->getLine()], ...$exception->getTrace()])
            ->filter(fn (array $frame): bool => isset($frame['file'], $frame['line']) && !str_starts_with($frame['file'], $vendor))
            ->take(self::MAX_FRAMES)
            ->values()
            ->mapWithKeys(fn (array $frame, int $index): array => [
                "frames.{$index}" => sprintf('%s:%d', Str::after($frame['file'], base_path().DIRECTORY_SEPARATOR), $frame['line']),
            ])
            ->all();
    }

    /**
     * @return array<string, scalar|null>
     */
    private static function context(Throwable $exception): array
    {
        if (!method_exists($exception, 'context')) {
            return [];
        }

        $context = $exception->context();

        if (!is_array($context)) {
            return [];
        }

        return collect(Arr::dot($context))
            ->filter(fn (mixed $value): bool => is_scalar($value) || $value === null)
            ->mapWithKeys(fn (mixed $value, string|int $key): array => ["context.{$key}" => $value])
            ->all();
    }
}
