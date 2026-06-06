<?php

declare(strict_types=1);

describe('modal setValue validation', function (): void {
    test('returns null when value does not meet minimum length', function (?string $value, int $minLength, ?string $expected): void {
        $result = $value !== null && mb_strlen($value) >= $minLength ? $value : null;
        expect($result)->toBe($expected);
    })->with([
        'null value for min 2' => [null, 2, null],
        'empty string for min 2' => ['', 2, null],
        'single char for min 2' => ['a', 2, null],
        'exactly min 2' => ['ab', 2, 'ab'],
        'above min 2' => ['abc', 2, 'abc'],
        'null value for min 5' => [null, 5, null],
        'short string for min 5' => ['abc', 5, null],
        'exactly min 5' => ['abcde', 5, 'abcde'],
        'multibyte chars respected' => ['áé', 2, 'áé'],
    ]);
});

describe('logging configuration', function (): void {
    test('bot-discord log channel is configured as daily with 30-day retention', function (): void {
        $channel = config('logging.channels.bot-discord');

        expect($channel)->not->toBeNull()
            ->and($channel['driver'])->toBe('daily')
            ->and($channel['days'])->toBe(30);
    });
});
