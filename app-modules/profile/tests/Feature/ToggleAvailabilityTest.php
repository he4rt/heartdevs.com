<?php

declare(strict_types=1);

use He4rt\Profile\Actions\ToggleAvailability;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use Illuminate\Validation\ValidationException;

test('activate availability with start availability', function (): void {
    $profile = Profile::factory()->create([
        'available_for_proposals' => false,
    ]);

    $result = resolve(ToggleAvailability::class)->handle($profile, available: true, startAvailability: StartAvailability::Immediate);

    expect($result->available_for_proposals)->toBeTrue()
        ->and($result->start_availability)->toBe(StartAvailability::Immediate);
});

test('activate availability without start availability throws validation error', function (): void {
    $profile = Profile::factory()->create([
        'available_for_proposals' => false,
    ]);

    resolve(ToggleAvailability::class)->handle($profile, available: true);
})->throws(ValidationException::class);

test('deactivate availability preserves previous start availability', function (): void {
    $profile = Profile::factory()->create([
        'available_for_proposals' => true,
        'start_availability' => StartAvailability::OneWeek,
    ]);

    $result = resolve(ToggleAvailability::class)->handle($profile, available: false);

    expect($result->available_for_proposals)->toBeFalse()
        ->and($result->start_availability)->toBe(StartAvailability::OneWeek);
});

test('change start availability while keeping availability active', function (): void {
    $profile = Profile::factory()->create([
        'available_for_proposals' => true,
        'start_availability' => StartAvailability::Immediate,
    ]);

    $result = resolve(ToggleAvailability::class)->handle($profile, available: true, startAvailability: StartAvailability::TwoWeeks);

    expect($result->available_for_proposals)->toBeTrue()
        ->and($result->start_availability)->toBe(StartAvailability::TwoWeeks);
});

test('deactivate already inactive availability is idempotent', function (): void {
    $profile = Profile::factory()->create([
        'available_for_proposals' => false,
        'start_availability' => null,
    ]);

    $result = resolve(ToggleAvailability::class)->handle($profile, available: false);

    expect($result->available_for_proposals)->toBeFalse();
});
