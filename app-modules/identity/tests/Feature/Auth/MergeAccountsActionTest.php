<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\MergeAccountsAction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

test('moves external identities from current to old user', function (): void {
    $oldUser = User::factory()->create(['first_login_at' => now()]);
    $currentUser = User::factory()->create();

    $identity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $currentUser->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => 'github-123',
    ]);

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $identity->refresh();
    expect($identity->model_id)->toBe($oldUser->id);
});

test('deletes current user after merge', function (): void {
    $oldUser = User::factory()->create(['first_login_at' => now()]);
    $currentUser = User::factory()->create();
    $currentUserId = $currentUser->id;

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    expect(User::query()->find($currentUserId))->toBeNull();
});

test('reassigns connected_by from current to old user', function (): void {
    $oldUser = User::factory()->create(['first_login_at' => now()]);
    $currentUser = User::factory()->create();

    $identity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $oldUser->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => 'github-456',
        'connected_by' => $currentUser->id,
    ]);

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $identity->refresh();
    expect($identity->connected_by)->toBe($oldUser->id);
});

test('reassigns connected_by on soft-deleted identities', function (): void {
    $oldUser = User::factory()->create(['first_login_at' => now()]);
    $currentUser = User::factory()->create();

    $identity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $oldUser->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => 'github-789',
        'connected_by' => $currentUser->id,
        'deleted_at' => now(),
    ]);

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $identity->refresh();
    expect($identity->connected_by)->toBe($oldUser->id);
});

test('enriches old user when first_login_at is null', function (): void {
    $oldUser = User::factory()->create([
        'username' => 'old-etl-user',
        'name' => 'old-etl-user',
        'email' => null,
        'first_login_at' => null,
    ]);
    $currentUser = User::factory()->create([
        'username' => 'new-oauth-user',
        'name' => 'Daniel Reis',
        'email' => 'daniel@example.com',
    ]);

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $oldUser->refresh();
    expect($oldUser->email)->toBe('daniel@example.com')
        ->and($oldUser->name)->toBe('Daniel Reis')
        ->and($oldUser->first_login_at)->not->toBeNull();
});

test('does not enrich old user when first_login_at is already set', function (): void {
    $oldUser = User::factory()->create([
        'username' => 'active-user',
        'name' => 'Active User',
        'email' => 'active@example.com',
        'first_login_at' => now()->subMonth(),
    ]);
    $currentUser = User::factory()->create([
        'username' => 'new-user',
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $oldUser->refresh();
    expect($oldUser->email)->toBe('active@example.com')
        ->and($oldUser->name)->toBe('Active User')
        ->and($oldUser->username)->toBe('active-user');
});

test('skips username update on old user when it would collide', function (): void {
    User::factory()->create(['username' => 'blocked-name']);
    $oldUser = User::factory()->create([
        'username' => 'old-user',
        'name' => 'old-user',
        'first_login_at' => null,
    ]);
    $currentUser = User::factory()->create([
        'username' => 'blocked-name-2',
        'name' => 'Current Name',
        'email' => 'new@example.com',
    ]);

    // Simulate currentUser having the blocked username by setting it in-memory
    // after creation (the merge reads $source->username from the object)
    $currentUser->username = 'blocked-name';

    $action = new MergeAccountsAction();
    $action->execute($currentUser, $oldUser);

    $oldUser->refresh();
    expect($oldUser->username)->toBe('old-user');
});
