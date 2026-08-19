<?php

declare(strict_types=1);

use App\Livewire\ConnectionHub;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

test('merge confirmation modal exposes accessible dialog semantics', function (): void {
    $currentUser = User::factory()->create();
    $mergeTarget = User::factory()->create();

    $this->actingAs($currentUser);

    session()->put('oauth_merge_pending', [
        'conflicting_user_id' => $mergeTarget->id,
    ]);

    livewire(ConnectionHub::class)
        ->assertSet('showMergeModal', value: true)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('@keydown.escape.window="$wire.cancelMerge()"');
});

test('an api key provider opens the modal instead of redirecting', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->assertNoRedirect()
        ->assertSet('showApiKeyModal', value: true)
        ->assertSet('apiKeyProvider', 'devto');
});

test('an oauth provider still redirects to the oauth flow', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::GitHub)
        ->assertSet('showApiKeyModal', value: false)
        ->assertRedirect();
});

test('saving a valid key connects the provider and closes the modal', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => 12_345,
            'username' => 'danielhe4rt',
            'name' => 'Daniel',
        ]),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', 'a-valid-api-key')
        ->call('saveApiKey')
        ->assertHasNoErrors()
        ->assertSet('showApiKeyModal', value: false)
        ->assertSet('apiKey', '')
        ->assertNotified();

    $identity = ExternalIdentity::query()
        ->where('model_id', $user->id)
        ->where('provider', IdentityProvider::DevTo)
        ->sole();

    expect($identity->credentials_type)->toBe(CredentialsType::ApiKey)
        ->and($identity->metadata['username'])->toBe('danielhe4rt');
});

test('an invalid key keeps the modal open with an error and stores nothing', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', 'a-wrong-api-key')
        ->call('saveApiKey')
        ->assertHasErrors('apiKey')
        ->assertSet('showApiKeyModal', value: true)
        ->assertSet('apiKey', 'a-wrong-api-key');

    expect(ExternalIdentity::query()->where('provider', IdentityProvider::DevTo)->count())->toBe(0);
});

test('an empty key is rejected before any request is made', function (): void {
    Http::fake();

    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', '')
        ->call('saveApiKey')
        ->assertHasErrors(['apiKey' => 'required'])
        ->assertSet('showApiKeyModal', value: true);

    Http::assertNothingSent();
});

test('the connection hub groups providers by authentication method', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->assertSee(CredentialsType::OAuth2->getLabel())
        ->assertSee(CredentialsType::ApiKey->getLabel())
        ->assertSee(IdentityProvider::DevTo->getLabel());
});
