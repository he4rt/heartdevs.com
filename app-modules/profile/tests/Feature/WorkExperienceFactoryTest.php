<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('factory never generates current experiment with end_date filled in', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences->each(static function (WorkExperience $experience): void {
        if ($experience->is_currently_working_here) {
            expect($experience->end_date)->toBeNull();
        }
    });
});

test('non-current factory generates end_date greater than or equal to start_date', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences
        ->reject(fn (WorkExperience $experience): bool => $experience->is_currently_working_here)
        ->each(static function (WorkExperience $experience): void {
            expect($experience->end_date)->not->toBeNull()
                ->and($experience->end_date->greaterThanOrEqualTo($experience->start_date))->toBeTrue();
        });
});

test('state current generates current experience with end_date null', function (): void {
    $experience = WorkExperience::factory()->current()->create();

    expect($experience->is_currently_working_here)->toBeTrue()
        ->and($experience->end_date)->toBeNull();
});
