<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\IssueMobileExchangeCodeAction;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Auth;

test('exchange trades a valid code for a token pair', function (): void {
    $user = User::factory()->create();
    $code = resolve(IssueMobileExchangeCodeAction::class)->execute($user);

    $this->postJson('/api/mobile/auth/exchange', ['code' => $code])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
        ->assertJson(['token_type' => 'bearer']);
});

test('exchange consumes the code, so it cannot be reused', function (): void {
    $user = User::factory()->create();
    $code = resolve(IssueMobileExchangeCodeAction::class)->execute($user);

    $this->postJson('/api/mobile/auth/exchange', ['code' => $code])->assertOk();
    $this->postJson('/api/mobile/auth/exchange', ['code' => $code])->assertUnauthorized();
});

test('exchange rejects an unknown code', function (): void {
    $this->postJson('/api/mobile/auth/exchange', ['code' => 'does-not-exist'])
        ->assertUnauthorized();
});

test('me returns the authenticated user', function (): void {
    $user = User::factory()->create(['username' => 'he4rtdev']);
    $token = Auth::guard('api')->login($user);

    $this->getJson('/api/mobile/me', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJson(['id' => $user->id, 'username' => 'he4rtdev']);
});

test('me rejects a request without a token', function (): void {
    $this->getJson('/api/mobile/me')->assertUnauthorized();
});

test('refresh issues a new token', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $response = $this->postJson('/api/mobile/auth/refresh', [], ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

    expect($response->json('access_token'))->not->toBe($token);
});

test('refresh rejects a missing token', function (): void {
    $this->postJson('/api/mobile/auth/refresh')->assertUnauthorized();
});

test('logout invalidates the token', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $this->postJson('/api/mobile/auth/logout', [], ['Authorization' => "Bearer {$token}"])
        ->assertNoContent();

    $this->getJson('/api/mobile/me', ['Authorization' => "Bearer {$token}"])
        ->assertUnauthorized();
});
