<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\DTOs\ProfileSkillData;
use He4rt\Profile\Enums\SkillCategory;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Models\WorkExperience;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders skills with proficiency and years', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create();

    $skill = Skill::factory()->create([
        'name' => 'Rust',
        'category' => SkillCategory::Language,
    ]);

    ProfileSkill::factory()->for($profile)->create([
        'skill_id' => $skill->id,
        'proficiency' => SkillProficiency::Advanced,
        'years_experience' => 4,
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Skills')
        ->assertSee('Rust')
        ->assertSee(SkillProficiency::Advanced->getLabel())
        ->assertSee('4 anos');
});

it('sorts skills by name', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    foreach (['Zig', 'Ada', 'Rust'] as $name) {
        ProfileSkill::factory()->for($profile)->create([
            'skill_id' => Skill::factory()->create(['name' => $name])->id,
        ]);
    }

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect(array_map(fn (ProfileSkillData $skill): string => $skill->name, $data->skills))
        ->toBe(['Ada', 'Rust', 'Zig']);
});

it('renders work experiences with company, position and period', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create();

    WorkExperience::factory()->for($profile)->create([
        'company_name' => 'Empresa Antiga',
        'position' => 'Estagiário',
        'description' => 'Primeiro emprego.',
        'start_date' => '2019-01-01',
        'end_date' => '2020-07-01',
        'is_currently_working_here' => false,
    ]);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'ScyllaDB',
        'position' => 'Developer Advocate',
        'description' => 'Comunidade e conteúdo técnico.',
        'start_date' => '2023-01-01',
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Experiência profissional')
        ->assertSee('ScyllaDB')
        ->assertSee('Developer Advocate')
        ->assertSee('01/2023 — atual')
        ->assertSee('Comunidade e conteúdo técnico.')
        ->assertSee('Empresa Antiga')
        ->assertSee('01/2019 — 07/2020')
        ->assertSee('1 ano e 6 meses');
});

it('puts the ongoing job first', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    WorkExperience::factory()->for($profile)->create([
        'company_name' => 'Antiga',
        'start_date' => '2019-01-01',
        'end_date' => '2020-01-01',
        'is_currently_working_here' => false,
    ]);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'Atual',
        'start_date' => '2021-01-01',
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->experiences[0]->company)->toBe('Atual')
        ->and($data->experiences[0]->isCurrent)->toBeTrue()
        ->and($data->experiences[1]->company)->toBe('Antiga');
});

it('shows only the start date when a past job has no end date', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    WorkExperience::factory()->for($profile)->create([
        'start_date' => '2018-03-01',
        'end_date' => null,
        'is_currently_working_here' => false,
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->experiences[0]->period)->toBe('03/2018')
        ->and($data->experiences[0]->duration)->toBeNull();
});

it('spells durations in months, years, or both', function (int $months, string $expected): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    $start = now()->subMonths($months)->startOfMonth();

    WorkExperience::factory()->for($profile)->create([
        'start_date' => $start,
        'end_date' => $start->copy()->addMonths($months),
        'is_currently_working_here' => false,
    ]);

    expect(resolve(BuildPublicProfile::class)->handle($user)->experiences[0]->duration)->toBe($expected);
})->with([
    'one month' => [1, '1 mês'],
    'some months' => [7, '7 meses'],
    'exactly one year' => [12, '1 ano'],
    'years and months' => [30, '2 anos e 6 meses'],
]);

it('hides both sections when the profile has no resume', function (): void {
    User::factory()->create(['username' => 'vazio']);

    $this->get('/@vazio')
        ->assertOk()
        ->assertDontSee('Skills')
        ->assertDontSee('Experiência profissional');
});
