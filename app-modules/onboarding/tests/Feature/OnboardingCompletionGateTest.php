<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Support\Facades\Date;

test('a completed onboarding opens its completion gate', function (): void {
    $user = User::factory()->create();

    Onboarding::factory()->create([
        'user_id' => $user,
        'type' => OnboardingType::Welcome,
        'status' => OnboardingStatus::Completed,
        'completed_at' => now(),
    ]);

    expect(resolve(OnboardingCompletionGate::class)->isCompleted($user, OnboardingType::Welcome))
        ->toBeTrue();
});

test('a non-completed onboarding keeps its completion gate closed', function (OnboardingStatus $status): void {
    $user = User::factory()->create();

    Onboarding::factory()->create([
        'user_id' => $user,
        'type' => OnboardingType::Welcome,
        'status' => $status,
    ]);

    expect(resolve(OnboardingCompletionGate::class)->isCompleted($user, OnboardingType::Welcome))
        ->toBeFalse();
})->with([
    OnboardingStatus::InProgress,
    OnboardingStatus::Paused,
    OnboardingStatus::Rejected,
]);

test('a missing onboarding keeps its completion gate closed', function (): void {
    $user = User::factory()->create();

    expect(resolve(OnboardingCompletionGate::class)->isCompleted($user, OnboardingType::Welcome))
        ->toBeFalse();
});

test('completion of another onboarding type does not open the requested gate', function (): void {
    $user = User::factory()->create();

    Onboarding::factory()->create([
        'user_id' => $user,
        'type' => OnboardingType::Squads,
        'status' => OnboardingStatus::Completed,
        'completed_at' => now(),
    ]);

    expect(resolve(OnboardingCompletionGate::class)->isCompleted($user, OnboardingType::Welcome))
        ->toBeFalse();
});

test('the completion gate exposes when the onboarding was completed', function (): void {
    $user = User::factory()->create();
    $completedAt = Date::parse('2026-07-26 12:34:56 UTC');

    Onboarding::factory()->create([
        'user_id' => $user,
        'type' => OnboardingType::Welcome,
        'status' => OnboardingStatus::Completed,
        'completed_at' => $completedAt,
    ]);

    expect(resolve(OnboardingCompletionGate::class)->completedAt($user, OnboardingType::Welcome))
        ->toEqual($completedAt);
});

test('the completion gate does not expose a timestamp for a non-completed onboarding', function (): void {
    $user = User::factory()->create();

    Onboarding::factory()->create([
        'user_id' => $user,
        'type' => OnboardingType::Welcome,
        'status' => OnboardingStatus::InProgress,
        'completed_at' => now(),
    ]);

    expect(resolve(OnboardingCompletionGate::class)->completedAt($user, OnboardingType::Welcome))
        ->toBeNull();
});

test('the completion gate has no timestamp when the onboarding is missing', function (): void {
    $user = User::factory()->create();

    expect(resolve(OnboardingCompletionGate::class)->completedAt($user, OnboardingType::Welcome))
        ->toBeNull();
});
