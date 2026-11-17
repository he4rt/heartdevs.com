<?php

declare(strict_types=1);

use App\Support\Query\TableQuery;

it('accepts a page size from the allowed options', function (): void {
    expect(TableQuery::perPage(['per_page' => '50']))->toBe(50);
});

it('falls back to the default page size when the value is not allowed', function (mixed $perPage): void {
    expect(TableQuery::perPage(['per_page' => $perPage], 25))->toBe(25);
})->with([
    'too big' => ['999'],
    'not a number' => ['abc'],
    'an array' => [['10']],
    'missing' => [null],
]);

it('never returns a page lower than one', function (mixed $page, int $expected): void {
    expect(TableQuery::page(['page' => $page]))->toBe($expected);
})->with([
    'a valid page' => ['3', 3],
    'zero' => ['0', 1],
    'negative' => ['-4', 1],
    'not a number' => ['abc', 1],
]);
