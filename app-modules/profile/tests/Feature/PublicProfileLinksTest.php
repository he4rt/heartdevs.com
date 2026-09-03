<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Models\Profile;

beforeEach(function (): void {
    $this->withoutVite();
});

function connectIdentity(User $user, IdentityProvider $provider, array $metadata, ?CarbonInterface $disconnectedAt = null): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'provider' => $provider,
        'metadata' => $metadata,
        'connected_at' => now(),
        'disconnected_at' => $disconnectedAt,
    ]);
}

it('renders social links with their resolved urls', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    Profile::factory()->for($user)->create([
        'social_links' => [
            SocialPlatform::LinkedIn->value => 'danielhe4rt',
            SocialPlatform::Instagram->value => '@danielhe4rt',
            SocialPlatform::Website->value => 'https://he4rt.dev',
        ],
    ]);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Links')
        ->assertSee('https://linkedin.com/in/danielhe4rt')
        ->assertSee('https://instagram.com/danielhe4rt')
        ->assertSee('https://he4rt.dev');
});

it('links a connected github account', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    connectIdentity($user, IdentityProvider::GitHub, ['username' => 'danielhe4rt']);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('https://github.com/danielhe4rt');
});

it('shows a discord handle without a link', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    connectIdentity($user, IdentityProvider::Discord, ['username' => 'dani#0001']);

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->connectedAccounts)->toHaveCount(1)
        ->and($data->connectedAccounts[0]->handle)->toBe('dani#0001')
        ->and($data->connectedAccounts[0]->url)->toBeNull();

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('dani#0001');
});

it('skips disconnected accounts', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    connectIdentity($user, IdentityProvider::GitHub, ['username' => 'antigo'], now());

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->connectedAccounts)->toBeEmpty();

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertDontSee('antigo');
});

it('skips providers outside the supported set', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    connectIdentity($user, IdentityProvider::Steam, ['username' => 'steamhandle']);

    $data = resolve(BuildPublicProfile::class)->handle($user);

    expect($data->connectedAccounts)->toBeEmpty();

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertDontSee('steamhandle');
});

it('never leaks the email or the oauth tokens of a connected account', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    $identity = connectIdentity($user, IdentityProvider::GitHub, [
        'username' => 'danielhe4rt',
        'email' => 'segredo@he4rt.dev',
    ]);

    $response = $this->get('/@danielhe4rt')->assertOk();

    $response->assertSee('danielhe4rt')
        ->assertDontSee('segredo@he4rt.dev');

    $rawCredentials = (string) $identity->getRawOriginal('credentials');

    expect($rawCredentials)->not->toBeEmpty()
        ->and($response->getContent())->not->toContain($rawCredentials);
});

it('hides the links section when there is nothing to link', function (): void {
    User::factory()->create(['username' => 'vazio']);

    $this->get('/@vazio')
        ->assertOk()
        ->assertDontSee('Links');
});
