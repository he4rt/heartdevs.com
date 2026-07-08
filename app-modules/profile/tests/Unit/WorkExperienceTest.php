<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('durationInMonths retorna null quando passada e sem end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => null,
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBeNull();
});

test('durationInMonths usa now() quando is_currently_working_here', function (): void {
    $experience = new WorkExperience([
        'start_date' => now()->subMonths(10)->toDateString(),
        'end_date' => null,
        'is_currently_working_here' => true,
    ]);

    expect($experience->durationInMonths())->toBeGreaterThanOrEqual(9)
        ->and($experience->durationInMonths())->toBeLessThanOrEqual(11);
});

test('durationInMonths calcula meses entre start_date e end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => '2021-01-01',
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBe(12);
});
