<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileProject;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders each project with name, description and link', function (): void {
    $user = User::factory()->create(['username' => 'construtora']);
    $profile = Profile::factory()->for($user)->create();

    ProfileProject::factory()->for($profile)->create([
        'name' => 'Bot da Comunidade',
        'description' => 'Bot de Discord que cuida do check-in dos eventos.',
        'url' => 'https://github.com/construtora/bot',
    ]);

    $this->get('/@construtora')
        ->assertOk()
        ->assertSee('Projetos')
        ->assertSee('Bot da Comunidade')
        ->assertSee('Bot de Discord que cuida do check-in dos eventos.')
        ->assertSee('https://github.com/construtora/bot');
});

it('renders a name-only project without link or description', function (): void {
    $user = User::factory()->create(['username' => 'minimalista']);
    $profile = Profile::factory()->for($user)->create();

    ProfileProject::factory()->for($profile)->create([
        'name' => 'Projeto Secreto',
        'description' => null,
        'url' => null,
    ]);

    $this->get('/@minimalista')
        ->assertOk()
        ->assertSee('Projeto Secreto');
});

it('hides the section when there are no projects', function (): void {
    $user = User::factory()->create(['username' => 'sem-projetos']);

    Profile::factory()->for($user)->create(['about' => 'Só a bio.']);

    $this->get('/@sem-projetos')
        ->assertOk()
        ->assertDontSee('Projetos');
});

it('drops a url whose scheme is not http', function (): void {
    $user = User::factory()->create(['username' => 'esperto']);
    $profile = Profile::factory()->for($user)->create();

    ProfileProject::factory()->for($profile)->create([
        'name' => 'Projeto Malicioso',
        'url' => 'javascript:alert(1)',
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user->refresh());

    expect($data->projects)->toHaveCount(1)
        ->and($data->projects[0]->url)->toBeNull();

    $this->get('/@esperto')
        ->assertOk()
        ->assertSee('Projeto Malicioso')
        ->assertDontSee('javascript:alert(1)');
});

it('judges the url scheme regardless of case', function (string $url, ?string $expected): void {
    $user = User::factory()->create(['username' => 'caixa-alta']);
    $profile = Profile::factory()->for($user)->create();

    ProfileProject::factory()->for($profile)->create([
        'name' => 'Projeto Gritado',
        'url' => $url,
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user->refresh());

    expect($data->projects[0]->url)->toBe($expected);
})->with([
    'https maiusculo' => ['HTTPS://he4rt.dev', 'HTTPS://he4rt.dev'],
    'http misto' => ['HtTp://he4rt.dev', 'HtTp://he4rt.dev'],
    'javascript maiusculo' => ['JAVASCRIPT:alert(1)', null],
    'javascript misto' => ['JavaScript:alert(1)', null],
    'sem esquema' => ['he4rt.dev/projeto', null],
]);

it('lists the newest project first', function (): void {
    $user = User::factory()->create(['username' => 'cronologico']);
    $profile = Profile::factory()->for($user)->create();

    ProfileProject::factory()->for($profile)->create([
        'name' => 'Projeto Antigo',
        'created_at' => now()->subYear(),
    ]);
    ProfileProject::factory()->for($profile)->create([
        'name' => 'Projeto Novo',
        'created_at' => now(),
    ]);

    $data = resolve(BuildPublicProfile::class)->handle($user->refresh());

    expect($data->projects[0]->name)->toBe('Projeto Novo')
        ->and($data->projects[1]->name)->toBe('Projeto Antigo');
});
