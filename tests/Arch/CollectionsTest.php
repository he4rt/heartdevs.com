<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
 * Gate for .ai/rules/laravel-conventions.md: lists go through collections, not native array functions.
 * Pest's arch() cannot see an unqualified native call inside a namespace, so this reads the source.
 */

const NATIVE_ARRAY_CALLS = '/(?<!->|::|function )\b(array_values|array_map|array_filter|array_unique|array_column|sort|rsort|usort|uasort|ksort)\(/';

test('application code builds lists with collections', function (string $path, string $source): void {
    preg_match_all(NATIVE_ARRAY_CALLS, $source, $calls);

    expect($calls[1])->toBe([], "{$path} calls a native array function");
})->with(function (): array {
    $root = dirname(__DIR__, 2);

    $files = Finder::create()
        ->files()
        ->name('*.php')
        ->in([$root.'/app', $root.'/routes', ...glob($root.'/app-modules/*/src') ?: [], ...glob($root.'/app-modules/*/routes') ?: []]);

    return collect($files)
        ->mapWithKeys(fn (SplFileInfo $file): array => [
            mb_substr($file->getPathname(), mb_strlen($root) + 1) => [mb_substr($file->getPathname(), mb_strlen($root) + 1), (string) file_get_contents($file->getPathname())],
        ])
        ->all();
});
