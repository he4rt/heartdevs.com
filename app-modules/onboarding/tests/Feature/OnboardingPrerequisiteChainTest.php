<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Exceptions\PrerequisiteNotMetException;
use He4rt\Onboarding\Models\Onboarding;

test('starting Squads without a completed Welcome is blocked with the prerequisite as reason', function (): void {
    $user = User::factory()->create();

    expect(fn () => resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads))
        ->toThrow(function (PrerequisiteNotMetException $exception): void {
            expect($exception->type)->toBe(OnboardingType::Squads)
                ->and($exception->prerequisite)->toBe(OnboardingType::Welcome);
        });

    expect(Onboarding::query()->count())->toBe(0);
});

test('starting Squads with a Welcome still in progress is blocked', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->create(['status' => OnboardingStatus::InProgress]);

    expect(fn () => resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads))
        ->toThrow(PrerequisiteNotMetException::class);

    expect(Onboarding::query()->where('type', OnboardingType::Squads)->exists())->toBeFalse();
});

test('a completed Welcome unlocks the Squads onboarding', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads);

    expect($onboarding->exists)->toBeTrue()
        ->and($onboarding->type)->toBe(OnboardingType::Squads)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress);
});

test('Welcome has no prerequisites and starts without any prior onboarding', function (): void {
    $user = User::factory()->create();

    expect(OnboardingType::Welcome->handler()->prerequisites())->toBeEmpty();

    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Welcome);

    expect($onboarding->status)->toBe(OnboardingStatus::InProgress);
});
