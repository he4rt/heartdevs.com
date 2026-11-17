<?php

declare(strict_types=1);

namespace He4rt\Identity\Tests\Feature\Teams;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Artisan::call('sync:permissions');
});

it('allows super admin to perform all team actions', function (): void {
    $superUser = User::factory()->create();
    $superUser->assignRole(Roles::SuperAdmin->value);

    $team = Team::factory()->create();

    expect(Gate::forUser($superUser)->allows('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('view', $team))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('create', Team::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('update', $team))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('delete', $team))->toBeTrue();
});

it('denies viewAny and create without permission', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Team::class))->toBeTrue();
});

it('denies the team owner without the permission', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $owner->id]);

    expect(Gate::forUser($owner)->denies('view', $team))->toBeTrue()
        ->and(Gate::forUser($owner)->denies('update', $team))->toBeTrue();
});

it('allows a user granted a specific team permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Team::class));

    expect(Gate::forUser($user)->allows('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Team::class))->toBeTrue();
});
