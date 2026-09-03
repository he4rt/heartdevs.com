<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildProfileCard;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Models\WorkExperience;

it('prefers the headline over the current role', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create(['headline' => 'Developer Advocate']);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'ScyllaDB',
        'position' => 'DevRel',
    ]);

    expect(resolve(BuildProfileCard::class)->handle($user)->role)
        ->toBe('Developer Advocate');
});

it('falls back to position and company when there is no headline', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create(['headline' => null]);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'ScyllaDB',
        'position' => 'DevRel',
    ]);

    expect(resolve(BuildProfileCard::class)->handle($user)->role)
        ->toBe('DevRel · ScyllaDB');
});

it('has no role when neither headline nor current job exists', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    Profile::factory()->for($user)->create(['headline' => null]);

    expect(resolve(BuildProfileCard::class)->handle($user)->role)->toBeNull();
});

it('limits skills and counts the remainder', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create();

    foreach (['Ada', 'Basic', 'Cobol', 'Dart', 'Elixir'] as $name) {
        ProfileSkill::factory()->for($profile)->create([
            'skill_id' => Skill::factory()->create(['name' => $name])->id,
        ]);
    }

    $card = resolve(BuildProfileCard::class)->handle($user);

    expect($card->skills)->toBe(['Ada', 'Basic', 'Cobol'])
        ->and($card->remainingSkills)->toBe(2);
});

it('builds initials from the name and falls back to the username', function (): void {
    $named = User::factory()->create(['name' => 'Daniel Reis', 'username' => 'danielhe4rt']);
    $unnamed = User::factory()->create(['name' => '42', 'username' => 'zeta']);

    expect(resolve(BuildProfileCard::class)->handle($named)->initials)->toBe('DR')
        ->and(resolve(BuildProfileCard::class)->handle($unnamed)->initials)->toBe('Z');
});
