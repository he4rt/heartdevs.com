<?php

declare(strict_types=1);

namespace App\Support\Query;

use Illuminate\Support\Arr;

/**
 * Tables read `filter[...]`, `sort`, `page` and `per_page` from the URL through spatie/laravel-query-builder.
 */
final class TableQuery
{
    public const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** @param  array<array-key, mixed>  $parameters */
    public static function perPage(array $parameters, int $default = 25): int
    {
        $perPage = is_numeric($parameters['per_page'] ?? null) ? (int) $parameters['per_page'] : 0;

        return in_array($perPage, self::PER_PAGE_OPTIONS, strict: true) ? $perPage : $default;
    }

    /** @param  array<array-key, mixed>  $parameters */
    public static function page(array $parameters): int
    {
        $page = is_numeric($parameters['page'] ?? null) ? (int) $parameters['page'] : 1;

        return max(1, $page);
    }

    /**
     * A filter value is a string, or a list when the URL repeats the key.
     */
    public static function term(mixed $value): string
    {
        return collect(Arr::wrap($value))->filter(fn (mixed $item): bool => is_scalar($item))->implode(',');
    }
}
