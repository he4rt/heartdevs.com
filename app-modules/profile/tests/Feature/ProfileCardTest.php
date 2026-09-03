<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Support\PublicProfileCache;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->withoutVite();

    $this->viewer = User::factory()->create();
});

it('renders the card for an authenticated viewer', function (): void {
    $user = User::factory()->create([
        'name' => 'Daniel Reis',
        'username' => 'danielhe4rt',
    ]);

    $profile = Profile::factory()->for($user)->create([
        'headline' => 'Developer Advocate na He4rt',
        'available_for_proposals' => true,
    ]);

    ProfileSkill::factory()->for($profile)->create([
        'skill_id' => Skill::factory()->create(['name' => 'Rust'])->id,
    ]);

    $this->actingAs($this->viewer)
        ->get('/@danielhe4rt/card')
        ->assertOk()
        ->assertSee('Daniel Reis')
        ->assertSee('@danielhe4rt')
        ->assertSee('Developer Advocate na He4rt')
        ->assertSee('Rust')
        ->assertSee('Aberto a propostas')
        ->assertSee('/@danielhe4rt', escape: false)
        ->assertHeader('Cache-Control', 'max-age=600, private');
});

it('shows at most three skills and counts the rest', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $profile = Profile::factory()->for($user)->create();

    foreach (['Ada', 'Basic', 'Cobol', 'Dart', 'Elixir'] as $name) {
        ProfileSkill::factory()->for($profile)->create([
            'skill_id' => Skill::factory()->create(['name' => $name])->id,
        ]);
    }

    $this->actingAs($this->viewer)
        ->get('/@danielhe4rt/card')
        ->assertOk()
        ->assertSee('Ada')
        ->assertSee('Basic')
        ->assertSee('Cobol')
        ->assertDontSee('Dart')
        ->assertDontSee('Elixir')
        ->assertSee('+2');
});

it('renders a card for a user without a profile', function (): void {
    User::factory()->create([
        'name' => 'Sem Perfil',
        'username' => 'semperfil',
    ]);

    $this->actingAs($this->viewer)
        ->get('/@semperfil/card')
        ->assertOk()
        ->assertSee('Sem Perfil')
        ->assertSee('@semperfil');
});

it('blocks guests', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);

    $this->get('/@danielhe4rt/card')->assertUnauthorized();
});

it('returns 404 for a banned user', function (): void {
    User::factory()->create([
        'username' => 'banido',
        'banned_at' => now(),
    ]);

    $this->actingAs($this->viewer)
        ->get('/@banido/card')
        ->assertNotFound();
});

it('returns 404 for an unknown username', function (): void {
    $this->actingAs($this->viewer)
        ->get('/@ninguem/card')
        ->assertNotFound();
});

it('reuses the cached profile instead of rebuilding it', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    Profile::factory()->for($user)->create(['headline' => 'Developer Advocate']);

    $this->actingAs($this->viewer)->get('/@danielhe4rt/card')->assertOk();

    $queries = 0;

    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries++;
    });

    $this->actingAs($this->viewer)->get('/@danielhe4rt/card')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue()
        ->and($queries)->toBeLessThan(3);
});

it('throttles a burst of card requests from the same viewer', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);

    $this->actingAs($this->viewer);

    foreach (range(1, 120) as $ignored) {
        $this->get('/@danielhe4rt/card')->assertOk();
    }

    $this->get('/@danielhe4rt/card')->assertStatus(429);
});

it('counts the card throttle per viewer, not per profile', function (): void {
    User::factory()->create(['username' => 'primeiro']);
    User::factory()->create(['username' => 'segundo']);

    $this->actingAs($this->viewer);

    foreach (range(1, 120) as $ignored) {
        $this->get('/@primeiro/card')->assertOk();
    }

    $this->get('/@segundo/card')->assertStatus(429);
});
