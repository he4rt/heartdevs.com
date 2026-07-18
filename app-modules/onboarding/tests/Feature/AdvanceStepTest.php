<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\AdvanceStep;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use Illuminate\Validation\ValidationException;

test('conclude a welcome onboarding', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        User::factory()->create(),
        OnboardingType::Welcome
    );

    /**
     * Payload para termo ate definicao
     * do DTO
     */
    $payload = [
        'data' => [
            'terms' => true,
            'github' => 'arthurbazzz',
        ],
    ];

    resolve(AdvanceStep::class)->handle($onboarding, $payload);

    $onboarding->refresh();

    expect($onboarding->steps->sole()->status)
        ->toBe(OnboardingStepStatus::Done);

    expect($onboarding->status)
        ->toBe(OnboardingStatus::Completed)
        ->and($onboarding->completed_at)->not->toBeNull();
});

test('invalid form dont proceed', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        User::factory()->create(),
        OnboardingType::Welcome,
    );

    $payload = [];

    expect(fn () => resolve(AdvanceStep::class)->handle($onboarding, $payload))
        ->toThrow(ValidationException::class);

    $onboarding->refresh();

    expect($onboarding->steps()->sole()->status)
        ->toBe(OnboardingStepStatus::Pending)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress);
});
