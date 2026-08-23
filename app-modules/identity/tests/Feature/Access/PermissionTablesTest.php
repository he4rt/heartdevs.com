<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

test('permission tables exist', function (): void {
    expect(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('model_has_roles'))->toBeTrue()
        ->and(Schema::hasTable('model_has_permissions'))->toBeTrue()
        ->and(Schema::hasTable('role_has_permissions'))->toBeTrue();
});

test('model_morph_key stores a uuid, not a bigint', function (): void {
    expect(Schema::getColumnType('model_has_roles', 'model_id'))->toBe('uuid')
        ->and(Schema::getColumnType('model_has_permissions', 'model_id'))->toBe('uuid');
});

test('role timestamps are timezone aware', function (): void {
    expect(Schema::getColumnType('roles', 'created_at', fullDefinition: true))
        ->toContain('with time zone')
        ->and(Schema::getColumnType('permissions', 'updated_at', fullDefinition: true))
        ->toContain('with time zone');
});

test('assigning a role to a uuid user keeps the uuid intact', function (): void {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'moderation:viewer', 'guard_name' => 'web']);

    $user->assignRole($role);

    $pivot = DB::table('model_has_roles')
        ->where('role_id', $role->id)
        ->sole();

    expect($pivot->model_id)->toBe($user->id)
        ->and($user->fresh()->hasRole('moderation:viewer'))->toBeTrue();
});

test('the pivot stores the morph alias instead of the fqcn', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'discord:viewer', 'guard_name' => 'web']));

    expect(DB::table('model_has_roles')->value('model_type'))->toBe('user');
});
