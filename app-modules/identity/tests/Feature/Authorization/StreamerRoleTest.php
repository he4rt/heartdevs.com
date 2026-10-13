<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Spatie\Permission\Models\Role;

test('the streamer role exists after the migrations, without seeding', function (): void {
    expect(Role::findByName(UserRole::Streamer->value, UserRole::GUARD))->toBeInstanceOf(Role::class);
});

test('streamer can use the streamer tools', function (): void {
    $user = User::factory()->streamer()->create();

    expect($user->can('use-streamer-tools'))->toBeTrue();
});

test('member without the streamer role cannot use the streamer tools', function (): void {
    $user = User::factory()->create();

    expect($user->can('use-streamer-tools'))->toBeFalse();
});

test('super admin can use the streamer tools without the streamer role', function (): void {
    $user = User::factory()->superAdmin()->create();

    expect($user->hasRole(UserRole::Streamer))->toBeFalse()
        ->and($user->can('use-streamer-tools'))->toBeTrue();
});
