<?php

declare(strict_types=1);

use He4rt\Profile\Actions\SyncProfileSkills;
use He4rt\Profile\DTOs\ProfileSkillDTO;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// O catálogo é semeado pela própria migration, então referenciamos os slugs reais.
function skill(string $slug): Skill
{
    return Skill::query()->where('slug', $slug)->sole();
}

test('sync attaches new skills with proficiency and years', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');
    $laravel = skill('laravel');

    resolve(SyncProfileSkills::class)->handle($profile, [
        new ProfileSkillDTO($php->id, SkillProficiency::Advanced, 5),
        new ProfileSkillDTO($laravel->id, SkillProficiency::Expert, 3),
    ]);

    expect($profile->profileSkills()->count())->toBe(2)
        ->and($profile->skills()->pluck('slug')->all())->toEqualCanonicalizing(['php', 'laravel']);

    $pivot = $profile->profileSkills()->where('skill_id', $php->id)->first();
    expect($pivot->proficiency)->toBe(SkillProficiency::Advanced)
        ->and($pivot->years_experience)->toBe(5);
});

test('sync updates existing skill without duplicating', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');

    $action = resolve(SyncProfileSkills::class);
    $action->handle($profile, [new ProfileSkillDTO($php->id, SkillProficiency::Beginner, 1)]);
    $action->handle($profile, [new ProfileSkillDTO($php->id, SkillProficiency::Expert, 8)]);

    expect($profile->profileSkills()->count())->toBe(1);

    $pivot = $profile->profileSkills()->first();
    expect($pivot->proficiency)->toBe(SkillProficiency::Expert)
        ->and($pivot->years_experience)->toBe(8);
});

test('sync removes skills that are not in the payload', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');
    $laravel = skill('laravel');

    $action = resolve(SyncProfileSkills::class);
    $action->handle($profile, [
        new ProfileSkillDTO($php->id, SkillProficiency::Advanced, 5),
        new ProfileSkillDTO($laravel->id, SkillProficiency::Advanced, 5),
    ]);

    $action->handle($profile, [new ProfileSkillDTO($php->id, SkillProficiency::Advanced, 5)]);

    expect($profile->skills()->pluck('slug')->all())->toBe(['php']);
});

test('sync with empty payload clears all skills', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');

    $action = resolve(SyncProfileSkills::class);
    $action->handle($profile, [new ProfileSkillDTO($php->id, SkillProficiency::Advanced, 5)]);
    $action->handle($profile, []);

    expect($profile->profileSkills()->count())->toBe(0);
});

test('sync rejects duplicate skill in the same payload', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');

    resolve(SyncProfileSkills::class)->handle($profile, [
        new ProfileSkillDTO($php->id, SkillProficiency::Beginner, 1),
        new ProfileSkillDTO($php->id, SkillProficiency::Expert, 2),
    ]);
})->throws(ValidationException::class);

test('sync rejects years_experience outside 0-50 range', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');

    resolve(SyncProfileSkills::class)->handle($profile, [
        new ProfileSkillDTO($php->id, SkillProficiency::Advanced, 51),
    ]);
})->throws(ValidationException::class);

test('sync rejects unknown skills', function (): void {
    $profile = Profile::factory()->create();

    resolve(SyncProfileSkills::class)->handle($profile, [
        new ProfileSkillDTO(Str::uuid()->toString(), SkillProficiency::Advanced, 5),
    ]);
})->throws(ValidationException::class);

test('sync accepts null years_experience', function (): void {
    $profile = Profile::factory()->create();
    $php = skill('php');

    resolve(SyncProfileSkills::class)->handle($profile, [
        new ProfileSkillDTO($php->id, SkillProficiency::Advanced),
    ]);

    expect($profile->profileSkills()->first()->years_experience)->toBeNull();
});
