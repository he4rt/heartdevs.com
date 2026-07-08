<?php

declare(strict_types=1);

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;

test('profile has many work experiences', function (): void {
    $profile = Profile::factory()->create();
    WorkExperience::factory()->count(3)->create(['profile_id' => $profile->id]);

    expect($profile->workExperiences)->toHaveCount(3)
        ->and($profile->workExperiences->first())->toBeInstanceOf(WorkExperience::class);
});

test('work experiences are ordered: current first, then start_date desc', function (): void {
    $profile = Profile::factory()->create();

    $antiga = WorkExperience::factory()->create([
        'profile_id' => $profile->id,
        'is_currently_working_here' => false,
        'start_date' => '2018-01-01',
        'end_date' => '2019-01-01',
    ]);
    $recente = WorkExperience::factory()->create([
        'profile_id' => $profile->id,
        'is_currently_working_here' => false,
        'start_date' => '2021-01-01',
        'end_date' => '2022-01-01',
    ]);
    $atual = WorkExperience::factory()->current()->create([
        'profile_id' => $profile->id,
        'start_date' => '2020-01-01',
    ]);

    $ids = $profile->workExperiences()->pluck('id')->all();

    expect($ids)->toBe([$atual->id, $recente->id, $antiga->id]);
});
