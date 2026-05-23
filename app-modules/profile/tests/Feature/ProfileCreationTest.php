<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

test('profile is created when user joins tenant', function (): void {
    $user = User::factory()->create([
        'username' => 'danielhe4rt',
    ]);
    $tenant = Tenant::factory()->create([
        'name' => 'He4rt Developers',
    ]);

    $tenant->members()->attach($user);

    $profile = Profile::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($tenant)
        ->sole();

    expect($profile->nickname)->toBeNull()
        ->and($profile->headline)->toBeNull()
        ->and($profile->available_for_proposals)->toBeFalse();
});

test('profile is not duplicated when it already exists', function (): void {
    $user = User::factory()->create([
        'username' => 'danielhe4rt',
    ]);
    $tenant = Tenant::factory()->create([
        'name' => 'He4rt Developers',
    ]);

    $existingProfile = Profile::factory()->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'headline' => 'Existing headline',
    ]);

    $tenant->members()->attach($user);

    expect(Profile::query()
        ->whereBelongsTo($user)
        ->whereBelongsTo($tenant)
        ->count())->toBe(1)
        ->and($existingProfile->fresh()->headline)->toBe('Existing headline');
});

test('member can have independent profiles in multiple tenants', function (): void {
    $user = User::factory()->create([
        'username' => 'danielhe4rt',
    ]);
    $firstTenant = Tenant::factory()->create([
        'name' => 'He4rt Developers',
    ]);
    $secondTenant = Tenant::factory()->create([
        'name' => 'Outro Tenant',
    ]);

    $firstTenant->members()->attach($user);
    $secondTenant->members()->attach($user);

    $profiles = Profile::query()
        ->whereBelongsTo($user)
        ->get();

    $tenantIds = $profiles->pluck('tenant_id')->sort()->values()->all();
    $expectedTenantIds = collect([$firstTenant->id, $secondTenant->id])->sort()->values()->all();

    expect($profiles)->toHaveCount(2)
        ->and($tenantIds)->toBe($expectedTenantIds)
        ->and($profiles->pluck('id')->unique())->toHaveCount(2);
});

test('factory creates a valid profile and supports optional states', function (): void {
    $profile = Profile::factory()->complete()->create();

    expect($profile->user_id)->not->toBeNull()
        ->and($profile->tenant_id)->not->toBeNull()
        ->and($profile->nickname)->not->toBeNull()
        ->and($profile->headline)->not->toBeNull()
        ->and($profile->available_for_proposals)->toBeTrue();
});
