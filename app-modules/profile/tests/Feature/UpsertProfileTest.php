<?php

declare(strict_types=1);

use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Models\Profile;
use Illuminate\Validation\ValidationException;

test('upsert profile updates all fields', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'nickname' => 'Dan',
        'birthdate' => '1995-03-15',
        'about' => 'Dev PHP apaixonado por Laravel',
        'headline' => 'Backend Developer',
        'seniority_level' => 'mid',
        'years_experience' => 5,
        'social_links' => [
            'instagram' => '@danielhe4rt',
            'website' => 'https://danielheart.dev',
        ],
    ]);

    $result = resolve(UpsertProfile::class)->handle($profile, $dto);

    expect($result->nickname)->toBe('Dan')
        ->and($result->birthdate->format('Y-m-d'))->toBe('1995-03-15')
        ->and($result->about)->toBe('Dev PHP apaixonado por Laravel')
        ->and($result->headline)->toBe('Backend Developer')
        ->and($result->seniority_level)->toBe(SeniorityLevel::Mid)
        ->and($result->years_experience)->toBe(5)
        ->and($result->social_links)->toMatchArray([
            'instagram' => '@danielhe4rt',
            'website' => 'https://danielheart.dev',
        ]);
});

test('upsert profile updates partially without affecting other fields', function (): void {
    $profile = Profile::factory()->create([
        'nickname' => 'Dan',
        'about' => 'Dev PHP',
    ]);
    $dto = UpsertProfileDTO::fromArray([
        'headline' => 'Senior Dev',
    ]);

    $result = resolve(UpsertProfile::class)->handle($profile, $dto);

    expect($result->headline)->toBe('Senior Dev')
        ->and($result->nickname)->toBe('Dan')
        ->and($result->about)->toBe('Dev PHP');
});

test('upsert profile rejects about exceeding 500 characters', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'about' => str_repeat('a', 501),
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);

test('upsert profile rejects headline exceeding 100 characters', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'headline' => str_repeat('a', 101),
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);

test('upsert profile rejects nickname exceeding 100 characters', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'nickname' => str_repeat('a', 101),
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);

test('upsert profile rejects years_experience outside 0-50 range', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'years_experience' => 51,
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);

test('upsert profile rejects invalid social platform keys', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'social_links' => ['tiktok' => '@dan'],
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);

test('upsert profile accepts valid social platform keys', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'social_links' => [
            'instagram' => '@dan',
            'website' => 'https://dan.dev',
        ],
    ]);

    $result = resolve(UpsertProfile::class)->handle($profile, $dto);

    expect($result->social_links)->toMatchArray([
        'instagram' => '@dan',
        'website' => 'https://dan.dev',
    ]);
});

test('upsert profile does not modify profile when dto has all nulls', function (): void {
    $profile = Profile::factory()->create([
        'headline' => 'Original',
    ]);
    $dto = UpsertProfileDTO::fromArray([]);

    $result = resolve(UpsertProfile::class)->handle($profile, $dto);

    expect($result->headline)->toBe('Original');
});

test('upsert profile dto fromArray handles enum instances', function (): void {
    $dto = UpsertProfileDTO::fromArray([
        'seniority_level' => SeniorityLevel::Senior,
    ]);

    expect($dto->seniorityLevel)->toBe(SeniorityLevel::Senior);
});

test('upsert profile stores expected salary with decimal precision', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'expected_salary' => '7500.5',
    ]);

    $result = resolve(UpsertProfile::class)->handle($profile, $dto);

    expect($result->expected_salary)->toBe('7500.50');
});

test('upsert profile rejects negative expected salary', function (): void {
    $profile = Profile::factory()->create();
    $dto = UpsertProfileDTO::fromArray([
        'expected_salary' => '-100',
    ]);

    resolve(UpsertProfile::class)->handle($profile, $dto);
})->throws(ValidationException::class);
