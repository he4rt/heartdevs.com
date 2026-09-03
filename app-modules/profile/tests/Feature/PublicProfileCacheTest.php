<?php

declare(strict_types=1);

use App\Models\Address;
use App\Support\ApplicationLocale;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileProject;
use He4rt\Profile\Models\WorkExperience;
use He4rt\Profile\Support\PublicProfileCache;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;

beforeEach(function (): void {
    $this->withoutVite();
});

function visitProfile(string $username): TestResponse
{
    app()->forgetScopedInstances();

    return test()->get('/@'.$username);
}

function queriesWhileVisiting(string $username): int
{
    $count = 0;

    DB::listen(function (QueryExecuted $query) use (&$count): void {
        $count++;
    });

    visitProfile($username)->assertOk();

    return $count;
}

it('serves a second visit without touching the database', function (): void {
    $user = User::factory()->create(['username' => 'cacheado']);
    Profile::factory()->for($user)->create(['headline' => 'Backend Engineer']);

    expect(queriesWhileVisiting('cacheado'))->toBeGreaterThan(0);
    expect(queriesWhileVisiting('cacheado'))->toBe(1);
});

it('keys the cache by user id, so renaming leaves no orphan entry', function (): void {
    $user = User::factory()->create(['username' => 'antigo']);

    visitProfile('antigo')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();

    $user->update(['username' => 'novo']);

    visitProfile('novo')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();
});

it('drops the cache when the profile itself changes', function (): void {
    $user = User::factory()->create(['username' => 'editor']);
    $profile = Profile::factory()->for($user)->create(['headline' => 'Antes']);

    visitProfile('editor')->assertOk()->assertSee('Antes');

    $profile->update(['headline' => 'Depois']);

    visitProfile('editor')
        ->assertOk()
        ->assertSee('Depois')
        ->assertDontSee('Antes');
});

it('drops the cache when a profile-owned row changes', function (string $model, array $attributes, string $before, string $after): void {
    $user = User::factory()->create(['username' => 'dono']);
    $profile = Profile::factory()->for($user)->create();

    $row = $model::factory()->for($profile)->create($attributes);

    visitProfile('dono')->assertOk()->assertSee($before);

    $row->update([array_key_first($attributes) => $after]);

    visitProfile('dono')->assertOk()->assertSee($after)->assertDontSee($before);
})->with([
    'work experience' => [
        WorkExperience::class,
        ['company_name' => 'Empresa Antiga'],
        'Empresa Antiga',
        'Empresa Nova',
    ],
    'project' => [
        ProfileProject::class,
        ['name' => 'Projeto Antigo'],
        'Projeto Antigo',
        'Projeto Novo',
    ],
]);

it('drops the cache when a profile-owned row is deleted', function (): void {
    $user = User::factory()->create(['username' => 'apagador']);
    $profile = Profile::factory()->for($user)->create();

    $project = ProfileProject::factory()->for($profile)->create(['name' => 'Some Sumido']);

    visitProfile('apagador')->assertOk()->assertSee('Some Sumido');

    $project->delete();

    visitProfile('apagador')->assertOk()->assertDontSee('Some Sumido');
});

it('keys the cache by locale', function (): void {
    $user = User::factory()->create(['username' => 'poliglota']);

    expect(PublicProfileCache::key((string) $user->getKey(), ApplicationLocale::EN))
        ->toBe('public-profile:'.$user->getKey().':en')
        ->and(PublicProfileCache::key((string) $user->getKey(), ApplicationLocale::PT_BR))
        ->toBe('public-profile:'.$user->getKey().':pt_BR');
});

it('does not let a viewer in another language poison the public page', function (): void {
    $user = User::factory()->create(['username' => 'poliglota']);

    Profile::factory()->for($user)->create([
        'seniority_level' => SeniorityLevel::Mid,
    ]);

    $viewer = User::factory()->create();

    test()->actingAs($viewer)
        ->withSession([ApplicationLocale::SESSION_KEY => ApplicationLocale::EN])
        ->get('/@poliglota/card')
        ->assertOk();

    expect(Cache::get(PublicProfileCache::key((string) $user->getKey(), ApplicationLocale::EN))->seniority)
        ->toBe('Mid-Level');

    ApplicationLocale::apply(ApplicationLocale::PT_BR);

    visitProfile('poliglota')
        ->assertOk()
        ->assertSee('Pleno')
        ->assertDontSee('Mid-Level');
});

it('drops every locale entry when the profile changes', function (): void {
    $user = User::factory()->create(['username' => 'poliglota']);
    $profile = Profile::factory()->for($user)->create();

    foreach (ApplicationLocale::SUPPORTED as $locale) {
        Cache::put(PublicProfileCache::key((string) $user->getKey(), $locale), 'seed', 60);
    }

    $profile->update(['headline' => 'mudou']);

    foreach (ApplicationLocale::SUPPORTED as $locale) {
        expect(Cache::has(PublicProfileCache::key((string) $user->getKey(), $locale)))
            ->toBeFalse("a entrada de {$locale} sobreviveu ao forget()");
    }
});

it('drops the cache when the user is renamed', function (): void {
    $user = User::factory()->create([
        'name' => 'Nome Antigo',
        'username' => 'antigo',
    ]);

    Profile::factory()->for($user)->create();

    visitProfile('antigo')->assertOk()->assertSee('Nome Antigo');

    $user->update(['name' => 'Nome Novo', 'username' => 'novo']);

    visitProfile('novo')
        ->assertOk()
        ->assertSee('Nome Novo')
        ->assertSee('@novo')
        ->assertDontSee('Nome Antigo');
});

it('drops the cache when the address changes', function (): void {
    $user = User::factory()->create(['username' => 'viajante']);
    Profile::factory()->for($user)->create();

    $address = Address::factory()->forUser($user)->create([
        'city' => 'Recife',
        'state' => 'PE',
        'country' => 'BR',
    ]);

    visitProfile('viajante')->assertOk()->assertSee('Recife, PE, BR');

    $address->update(['city' => 'Olinda']);

    visitProfile('viajante')
        ->assertOk()
        ->assertSee('Olinda, PE, BR')
        ->assertDontSee('Recife');
});

it('drops the cache when a connected account changes', function (): void {
    $user = User::factory()->create(['username' => 'conectado']);
    Profile::factory()->for($user)->create();

    $identity = ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::GitHub,
        'metadata' => ['username' => 'handle-antigo'],
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    visitProfile('conectado')->assertOk()->assertSee('https://github.com/handle-antigo');

    $identity->update(['metadata' => ['username' => 'handle-novo']]);

    visitProfile('conectado')
        ->assertOk()
        ->assertSee('https://github.com/handle-novo')
        ->assertDontSee('handle-antigo');
});

it('drops the cache when the user avatar changes', function (): void {
    Storage::fake('public');

    $user = User::factory()->create(['username' => 'retratado']);

    visitProfile('retratado')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();

    $user->addMediaFromString('fake-png')
        ->usingFileName('avatar.png')
        ->toMediaCollection('avatar');

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeFalse();
});

it('keeps the cache when the character only gains experience', function (): void {
    $user = User::factory()->create(['username' => 'jogador']);

    $character = Character::factory()->create([
        'user_id' => $user->getKey(),
        'experience' => 100,
    ]);

    visitProfile('jogador')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();

    $character->increment('experience', 25);

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();
});

it('survives a morph id that arrives as a uuid object', function (): void {
    $user = User::factory()->create(['username' => 'uuidzeiro']);

    visitProfile('uuidzeiro')->assertOk();

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeTrue();

    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => Uuid::fromString((string) $user->getKey()),
        'provider' => IdentityProvider::GitHub,
        'metadata' => ['username' => 'handle'],
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    expect(Cache::has(PublicProfileCache::key((string) $user->getKey())))->toBeFalse();
});
