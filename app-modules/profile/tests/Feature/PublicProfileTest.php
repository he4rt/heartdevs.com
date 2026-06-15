<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

it('returns 200 for existing user with complete profile', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'janedoe']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'headline' => 'Backend Dev',
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@janedoe');

    $response->assertOk();
    $response->assertSee('Backend Dev');
});

it('renders minimal profile without crashing', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'novato']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'headline' => null,
        'about' => null,
        'skills' => null,
        'work_types' => null,
        'languages' => null,
        'social_links' => null,
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@novato');

    $response->assertOk();
    $response->assertSee($user->name);
    $response->assertDontSee('null');
    $response->assertDontSee('undefined');
});

it('returns 404 for non-existent user', function (): void {
    Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $response = $this->get('http://test.he4rtdevs.com/@fantasma');

    $response->assertNotFound();
});

it('returns 404 for user without profile in tenant', function (): void {
    Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);
    User::factory()->create(['username' => 'semprofile']);

    $response = $this->get('http://test.he4rtdevs.com/@semprofile');

    $response->assertNotFound();
});

it('does not show work type tags when work_types is empty', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'indisponivel']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'work_types' => null,
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@indisponivel');

    $response->assertOk();
    $response->assertDontSee('Início imediato');
});

it('renders work type tags when work_types are present', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'disponivel']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'work_types' => ['immediate', 'remote'],
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@disponivel');

    $response->assertOk();
    $response->assertSee('Início imediato');
    $response->assertSee('Remoto');
});

it('renders skills section when skills are present', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'devskill']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'skills' => [
            ['name' => 'PHP', 'category' => 'languages_frameworks'],
            ['name' => 'Laravel', 'category' => 'languages_frameworks'],
            ['name' => 'PostgreSQL', 'category' => 'infra_databases'],
        ],
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@devskill');

    $response->assertOk();
    $response->assertSee('Stack & Skills', false);
    $response->assertSee('PHP');
    $response->assertSee('Laravel');
    $response->assertSee('PostgreSQL');
});

it('does not render skills section when skills are null', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'noskills']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'skills' => null,
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@noskills');

    $response->assertOk();
    $response->assertDontSee('Stack');
});

it('renders languages when present', function (): void {
    $tenant = Tenant::factory()->create(['active' => true, 'domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'polyglot']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'languages' => [
            ['name' => 'Português', 'level' => 'Nativo'],
            ['name' => 'Inglês', 'level' => 'Intermediário'],
        ],
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@polyglot');

    $response->assertOk();
    $response->assertSee('Português');
    $response->assertSee('Nativo');
    $response->assertSee('Inglês');
});
