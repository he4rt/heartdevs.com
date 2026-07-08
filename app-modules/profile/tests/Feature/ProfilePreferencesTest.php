<?php

declare(strict_types=1);

use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\DB;

test('preferences default to an empty value object when never set', function (): void {
    $profile = Profile::factory()->create();

    expect($profile->preferences)->toBeInstanceOf(WorkPreferences::class)
        ->and($profile->preferences->isOpenToRemote)->toBeFalse()
        ->and($profile->preferences->employmentTypes)->toBeEmpty();
});

test('preferences cast persists and rehydrates a value object', function (): void {
    $profile = Profile::factory()->create();

    $profile->preferences = new WorkPreferences(
        hasDisability: true,
        willingToRelocate: true,
        isOpenToRemote: true,
        employmentTypes: [EmploymentType::IndependentContractor, EmploymentType::Freelancer],
    );
    $profile->save();

    $fresh = $profile->fresh();

    expect($fresh->preferences)->toBeInstanceOf(WorkPreferences::class)
        ->and($fresh->preferences->hasDisability)->toBeTrue()
        ->and($fresh->preferences->employmentTypes)->toBe([EmploymentType::IndependentContractor, EmploymentType::Freelancer]);

    $raw = DB::table('user_profiles')->where('id', $profile->id)->value('preferences');

    expect(json_decode((string) $raw, associative: true))->toMatchArray([
        'has_disability' => true,
        'is_open_to_remote' => true,
        'employment_types' => ['pj', 'freelance'],
    ]);
});

test('upsert profile stores preferences from an array payload', function (): void {
    $profile = Profile::factory()->create();

    $dto = UpsertProfileDTO::fromArray([
        'preferences' => [
            'is_open_to_remote' => true,
            'employment_types' => ['clt'],
        ],
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
    $profile->refresh();

    expect($profile->preferences->isOpenToRemote)->toBeTrue()
        ->and($profile->preferences->hasDisability)->toBeFalse()
        ->and($profile->preferences->employmentTypes)->toBe([EmploymentType::SalariedEmployee]);
});
