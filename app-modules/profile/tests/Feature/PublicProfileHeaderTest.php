<?php

declare(strict_types=1);

use App\Models\Address;
use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;

beforeEach(function (): void {
    $this->withoutVite();
});

function connectGithub(User $user, string $handle, ?CarbonInterface $disconnectedAt = null): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::GitHub,
        'metadata' => ['username' => $handle],
        'connected_at' => now(),
        'disconnected_at' => $disconnectedAt,
    ]);
}

it('renders every header field of a filled profile', function (): void {
    $user = User::factory()->create([
        'name' => 'Daniel Reis',
        'username' => 'danielhe4rt',
    ]);

    $profile = Profile::factory()->for($user)->create([
        'nickname' => 'dani',
        'headline' => 'Developer Advocate na He4rt',
        'available_for_proposals' => true,
    ]);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'ScyllaDB',
        'position' => 'Developer Advocate',
    ]);

    Address::factory()->forUser($user)->create([
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR',
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Daniel Reis')
        ->assertSee('@danielhe4rt')
        ->assertSee('dani')
        ->assertSee('Developer Advocate na He4rt')
        ->assertSee('ScyllaDB')
        ->assertSee('Disponível para propostas')
        ->assertSee('São Paulo, SP, BR');
});

it('renders an empty profile without leaking nulls or placeholders', function (): void {
    User::factory()->create([
        'name' => 'Perfil Vazio',
        'username' => 'vazio',
    ]);

    $this->get('/@vazio')
        ->assertOk()
        ->assertSee('Perfil Vazio')
        ->assertSee('@vazio')
        ->assertDontSee('Disponível para propostas')
        ->assertDontSee('null');
});

it('falls back to the picture of the connected github account', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    connectGithub($user, 'dani-no-github');

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('https://github.com/dani-no-github.png');
});

it('never guesses a github picture out of the he4rt username', function (): void {
    User::factory()->create(['username' => 'vazio', 'name' => 'Perfil Vazio']);

    $this->get('/@vazio')
        ->assertOk()
        ->assertDontSee('github.com/vazio.png')
        ->assertSee('PV');
});

it('draws initials instead of a picture when the github account is disconnected', function (): void {
    $user = User::factory()->create(['username' => 'saiu', 'name' => 'Fulano Silva']);

    connectGithub($user, 'fulano-gh', disconnectedAt: now());

    $this->get('/@saiu')
        ->assertOk()
        ->assertDontSee('github.com/fulano-gh.png')
        ->assertSee('FS');
});

it('never leaks private fields into the public page', function (): void {
    $user = User::factory()->create([
        'username' => 'privado',
        'email' => 'privado@he4rt.dev',
    ]);

    Profile::factory()->for($user)->create([
        'birthdate' => '1995-03-14',
        'expected_salary_min' => '15000.00',
        'expected_salary_max' => '25000.00',
    ]);

    Address::factory()->forUser($user)->create([
        'city' => 'São Paulo',
        'zip_code' => '01310-100',
    ]);

    $this->get('/@privado')
        ->assertOk()
        ->assertDontSee('privado@he4rt.dev')
        ->assertDontSee('1995-03-14')
        ->assertDontSee('15000')
        ->assertDontSee('25000')
        ->assertDontSee('01310-100');
});
