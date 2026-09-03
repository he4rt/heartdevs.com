<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Support\PublicProfileCache;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders a public profile without authentication', function (): void {
    User::factory()->create([
        'name' => 'Daniel Reis',
        'username' => 'danielhe4rt',
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Daniel Reis')
        ->assertSee('@danielhe4rt');
    $this->assertGuest();
});

it('lets the browser cache the page for the same window as the server', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age='.PublicProfileCache::TTL_SECONDS.', private');
});

it('returns 404 for an unknown username', function (): void {
    $this->get('/@ninguem')->assertNotFound();
});

it('returns 404 for a banned user', function (): void {
    User::factory()->create([
        'username' => 'banido',
        'banned_at' => now(),
    ]);

    $this->get('/@banido')->assertNotFound();
});

it('still renders the profile of a suspended user', function (): void {
    User::factory()->create([
        'name' => 'Suspenso Temporariamente',
        'username' => 'suspenso',
        'suspended_until' => now()->addDays(7),
    ]);

    $this->get('/@suspenso')
        ->assertOk()
        ->assertSee('Suspenso Temporariamente');
});

it('throttles a burst of requests from the same IP', function (): void {
    User::factory()->create(['username' => 'alvo']);

    foreach (range(1, 60) as $ignored) {
        $this->get('/@alvo')->assertOk();
    }

    $this->get('/@alvo')->assertStatus(429);
});

it('counts the throttle per IP, not per profile', function (): void {
    User::factory()->create(['username' => 'primeiro']);
    User::factory()->create(['username' => 'segundo']);

    foreach (range(1, 60) as $ignored) {
        $this->get('/@primeiro')->assertOk();
    }

    $this->get('/@segundo')->assertStatus(429);
});
