<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * Every Resource exposed by the admin panel must be governed by a policy. Without this
 * guard a Resource added later would reach the panel wide open, and nothing would say so.
 */
test('every admin panel resource is governed by a policy', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'coverage:probe', 'guard_name' => 'web']));

    $this->actingAs($user)->get('/admin')->assertOk();

    $unguarded = collect(Filament::getPanel('admin')->getResources())
        ->map(fn (string $resource): ?string => $resource::getModel())
        ->filter()
        ->reject(fn (string $model): bool => Gate::getPolicyFor($model) !== null)
        ->values()
        ->all();

    expect($unguarded)->toBeEmpty();
});
