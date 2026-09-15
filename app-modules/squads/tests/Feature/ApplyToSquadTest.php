<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use He4rt\Squads\Actions\ApplyToSquad;
use He4rt\Squads\Enums\ApplicationStatus;
use He4rt\Squads\Exceptions\ApplicationAlreadyPending;
use He4rt\Squads\Exceptions\NotAptForSquads;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadApplication;

test('an apt person opens a pending application', function (): void {
    $squad = Squad::factory()->create();
    $applicant = User::factory()->create();

    Onboarding::factory()->completed()->create([
        'user_id' => $applicant->id,
        'type' => OnboardingType::Squads,
    ]);

    $application = resolve(ApplyToSquad::class)->handle(
        applicant: $applicant,
        squad: $squad,
        message: 'Quero somar no back-end.',
    );

    expect($application->status)->toBe(ApplicationStatus::Pending)
        ->and($application->decided_by)->toBeNull()
        ->and($application->decided_at)->toBeNull();

    $this->assertDatabaseHas('squad_applications', [
        'squad_id' => $squad->id,
        'user_id' => $applicant->id,
        'status' => ApplicationStatus::Pending->value,
        'message' => 'Quero somar no back-end.',
    ]);
});

test('a person who is not apt is blocked and sent to the onboarding', function (?OnboardingType $type, ?OnboardingStatus $status): void {
    $squad = Squad::factory()->create();
    $applicant = User::factory()->create();

    if ($type instanceof OnboardingType) {
        Onboarding::factory()->create([
            'user_id' => $applicant->id,
            'type' => $type,
            'status' => $status,
        ]);
    }

    resolve(ApplyToSquad::class)->handle(
        applicant: $applicant,
        squad: $squad,
    );
})->with([
    'no onboarding at all' => [null, null],
    'squads onboarding in progress' => [OnboardingType::Squads, OnboardingStatus::InProgress],
    'only the welcome onboarding completed' => [OnboardingType::Welcome, OnboardingStatus::Completed],
])->throws(NotAptForSquads::class);

test('a second pending application to the same squad is blocked', function (): void {
    $squad = Squad::factory()->create();
    $applicant = User::factory()->create();

    Onboarding::factory()->completed()->create([
        'user_id' => $applicant->id,
        'type' => OnboardingType::Squads,
    ]);

    SquadApplication::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $applicant->id,
        'status' => ApplicationStatus::Pending,
    ]);

    resolve(ApplyToSquad::class)->handle(
        applicant: $applicant,
        squad: $squad,
    );
})->throws(ApplicationAlreadyPending::class);

test('a rejected application does not block a new one', function (): void {
    $squad = Squad::factory()->create();
    $applicant = User::factory()->create();

    Onboarding::factory()->completed()->create([
        'user_id' => $applicant->id,
        'type' => OnboardingType::Squads,
    ]);

    SquadApplication::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $applicant->id,
        'status' => ApplicationStatus::Rejected,
        'decided_at' => now(),
    ]);

    $application = resolve(ApplyToSquad::class)->handle(
        applicant: $applicant,
        squad: $squad,
    );

    expect($application->status)->toBe(ApplicationStatus::Pending)
        ->and(SquadApplication::query()->where('user_id', $applicant->id)->count())->toBe(2);
});

test('a pending application in another squad does not block this one', function (): void {
    $applicant = User::factory()->create();

    Onboarding::factory()->completed()->create([
        'user_id' => $applicant->id,
        'type' => OnboardingType::Squads,
    ]);

    SquadApplication::factory()->create([
        'user_id' => $applicant->id,
        'status' => ApplicationStatus::Pending,
    ]);

    $application = resolve(ApplyToSquad::class)->handle(
        applicant: $applicant,
        squad: Squad::factory()->create(),
    );

    expect($application->status)->toBe(ApplicationStatus::Pending);
});
