<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

it('returns 200 for existing user with complete profile', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->update(['domain' => 'test.he4rtdevs.com']);

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
    $tenant = Tenant::factory()->create();
    $tenant->update(['domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'novato']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@novato');

    $response->assertOk();
    $response->assertSee($user->name);
    $response->assertDontSee('null');
    $response->assertDontSee('undefined');
});

it('returns 404 for non-existent user', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->update(['domain' => 'test.he4rtdevs.com']);

    $response = $this->get('http://test.he4rtdevs.com/@fantasma');

    $response->assertNotFound();
});

it('returns 404 for user without profile in tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->update(['domain' => 'test.he4rtdevs.com']);
    User::factory()->create(['username' => 'semprofile']);

    $response = $this->get('http://test.he4rtdevs.com/@semprofile');

    $response->assertNotFound();
});

it('does not show available badge when available_for_proposals is false', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->update(['domain' => 'test.he4rtdevs.com']);

    $user = User::factory()->create(['username' => 'indisponivel']);
    Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'available_for_proposals' => false,
    ]);

    $response = $this->get('http://test.he4rtdevs.com/@indisponivel');

    $response->assertOk();
    $response->assertDontSee('Disponível');
    $response->assertDontSee('Indisponível');
});
