<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders the about section of a filled profile', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    Profile::factory()->for($user)->create([
        'about' => 'Trabalho com comunidade e dados.',
        'seniority_level' => SeniorityLevel::Senior,
        'years_experience' => 8,
        'start_availability' => StartAvailability::Immediate,
        'preferences' => new WorkPreferences(
            willingToRelocate: true,
            isOpenToRemote: true,
            employmentTypes: [EmploymentType::SalariedEmployee, EmploymentType::IndependentContractor],
        ),
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Sobre')
        ->assertSee('Trabalho com comunidade e dados.')
        ->assertSee(SeniorityLevel::Senior->getLabel())
        ->assertSee('8 anos')
        ->assertSee(StartAvailability::Immediate->getLabel())
        ->assertSee('Aberto a remoto')
        ->assertSee('Disposto a mudar de cidade')
        ->assertSee(EmploymentType::SalariedEmployee->getLabel())
        ->assertSee(EmploymentType::IndependentContractor->getLabel());
});

it('hides the whole about section when there is nothing to say', function (): void {
    User::factory()->create(['username' => 'vazio']);

    $this->get('/@vazio')
        ->assertOk()
        ->assertDontSee('Sobre')
        ->assertDontSee('Senioridade')
        ->assertDontSee('Aberto a remoto');
});

it('never exposes the age nor the birthdate', function (): void {
    $user = User::factory()->create(['username' => 'aniversariante']);

    Profile::factory()->for($user)->create([
        'birthdate' => now()->subYears(30)->subMonths(2)->toDateString(),
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user->refresh());
    expect($data)->not->toHaveProperty('age');

    $this->get('/@aniversariante')
        ->assertOk()
        ->assertDontSee('30 anos')
        ->assertDontSee('Idade')
        ->assertDontSee(now()->subYears(30)->subMonths(2)->format('Y-m-d'))
        ->assertDontSee(now()->subYears(30)->subMonths(2)->format('d/m/Y'));
});

it('never exposes the disability flag', function (): void {
    $user = User::factory()->create(['username' => 'pcd']);

    Profile::factory()->for($user)->create([
        'preferences' => new WorkPreferences(
            hasDisability: true,
            isOpenToRemote: true,
        ),
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user->refresh());

    expect($data)->not->toHaveProperty('hasDisability');
    expect($data->openToRemote)->toBeTrue();

    $this->get('/@pcd')
        ->assertOk()
        ->assertDontSee('deficiência', escape: false)
        ->assertDontSee('disability');
});

it('leaves availability off when the profile has no preferences', function (): void {
    $user = User::factory()->create();

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->openToRemote)->toBeFalse()
        ->and($data->willingToRelocate)->toBeFalse()
        ->and($data->employmentTypes)->toBeEmpty()
        ->and($data->seniority)->toBeNull();
});
