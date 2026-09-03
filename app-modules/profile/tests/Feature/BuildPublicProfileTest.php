<?php

declare(strict_types=1);

use App\Models\Address;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\DTOs\PublicProfileData;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;

function buildProfileFor(User $user): PublicProfileData
{
    return resolve(BuildPublicProfile::class)->handle($user);
}

it('leaves the location empty when the user has no address', function (): void {
    $user = User::factory()->create();

    expect(buildProfileFor($user)->location)->toBeNull();
});

it('joins city, state and country but never the zip code', function (): void {
    $user = User::factory()->create();

    Address::factory()->forUser($user)->create([
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR',
        'zip_code' => '01310-100',
    ]);

    expect(buildProfileFor($user)->location)->toBe('São Paulo, SP, BR');
});

it('skips the missing pieces of a partial address', function (): void {
    $user = User::factory()->create();

    Address::factory()->forUser($user)->create([
        'city' => 'Recife',
        'state' => null,
        'country' => 'BR',
    ]);

    expect(buildProfileFor($user)->location)->toBe('Recife, BR');
});

it('picks the ongoing experience as the current role', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    WorkExperience::factory()->for($profile)->create([
        'company_name' => 'Empresa Antiga',
        'position' => 'Estagiário',
        'is_currently_working_here' => false,
        'end_date' => now()->subYear(),
    ]);

    WorkExperience::factory()->for($profile)->current()->create([
        'company_name' => 'ScyllaDB',
        'position' => 'Developer Advocate',
    ]);

    $data = buildProfileFor($user);

    expect($data->currentPosition)->toBe('Developer Advocate')
        ->and($data->currentCompany)->toBe('ScyllaDB');
});

it('leaves the current role empty when no experience is ongoing', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();

    WorkExperience::factory()->for($profile)->create([
        'is_currently_working_here' => false,
        'end_date' => now()->subMonths(3),
    ]);

    $data = buildProfileFor($user);

    expect($data->currentPosition)->toBeNull()
        ->and($data->currentCompany)->toBeNull();
});

it('falls back to the connected github picture and leaves the cover empty', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::GitHub,
        'metadata' => ['username' => 'dani-no-github'],
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    $data = buildProfileFor($user);

    expect($data->avatarUrl)->toBe('https://github.com/dani-no-github.png')
        ->and($data->coverUrl)->toBeNull();
});

it('leaves the avatar empty when nothing was uploaded and no github is connected', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    expect(buildProfileFor($user)->avatarUrl)->toBeNull();
});
