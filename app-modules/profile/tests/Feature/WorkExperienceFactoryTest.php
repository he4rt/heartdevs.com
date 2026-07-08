<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('factory nunca gera experiencia atual com end_date preenchido', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences->each(static function (WorkExperience $experience): void {
        if ($experience->is_currently_working_here) {
            expect($experience->end_date)->toBeNull();
        }
    });
});

test('factory nao-atual gera end_date maior ou igual a start_date', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences
        ->reject(fn (WorkExperience $experience): bool => $experience->is_currently_working_here)
        ->each(static function (WorkExperience $experience): void {
            expect($experience->end_date)->not->toBeNull()
                ->and($experience->end_date->greaterThanOrEqualTo($experience->start_date))->toBeTrue();
        });
});

test('state current gera experiencia atual com end_date null', function (): void {
    $experience = WorkExperience::factory()->current()->create();

    expect($experience->is_currently_working_here)->toBeTrue()
        ->and($experience->end_date)->toBeNull();
});
