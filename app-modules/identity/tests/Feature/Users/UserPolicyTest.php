<?php

declare(strict_types=1);

namespace He4rt\Users\Tests\Feature;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Artisan::call('sync:permissions');
});

it('allows super admin to perform all actions', function (): void {
    $superUser = User::factory()->create();
    $superUser->assignRole(Roles::SuperAdmin->value);

    $user = User::factory()->create();

    expect(Gate::forUser($superUser)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('view', $user))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('create', User::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('update', $user))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('delete', $user))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('restore', $user))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('forceDelete', $user))->toBeTrue();
});

it('denies a user without permissions every action', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    expect(Gate::forUser($user)->denies('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $otherUser))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', User::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', $otherUser))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $otherUser))->toBeTrue()
        ->and(Gate::forUser($user)->denies('restore', $otherUser))->toBeTrue()
        ->and(Gate::forUser($user)->denies('forceDelete', $otherUser))->toBeTrue();
});

it('denies updating own record without the update permission', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies('update', $user))->toBeTrue();
});

it('allows a user granted the update permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Update->buildPermissionFor(User::class));

    $otherUser = User::factory()->create();

    expect(Gate::forUser($user)->allows('update', $otherUser))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $otherUser))->toBeTrue();
});
