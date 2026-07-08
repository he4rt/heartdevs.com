<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('durationInMonths returns null when passed and without end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => null,
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBeNull();
});

test('durationInMonths uses now() when is_currently_working_here', function (): void {
    $experience = new WorkExperience([
        'start_date' => now()->subMonths(10)->toDateString(),
        'end_date' => null,
        'is_currently_working_here' => true,
    ]);

    expect($experience->durationInMonths())->toBeGreaterThanOrEqual(9)
        ->and($experience->durationInMonths())->toBeLessThanOrEqual(11);
});

test('durationInMonths calculates months between start_date and end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => '2021-01-01',
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBe(12);
});
