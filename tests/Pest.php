<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->group('unit')
    ->in('Unit', '../app-modules/*/tests/Unit');

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->group('feature')
    ->in('Feature', '../app-modules/*/tests/Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Panel access
|--------------------------------------------------------------------------
|
| Admin panel access is granted by roles, so a bare factory user is locked out.
| This builds a user carrying exactly the permissions a test needs.
|
*/

function panelUserWith(string ...$permissions): User
{
    $user = User::factory()->create();

    $role = Role::query()->firstOrCreate([
        'name' => 'test:'.md5(implode(',', $permissions)),
        'guard_name' => 'web',
    ]);

    $role->syncPermissions(
        collect($permissions)->map(fn (string $permission): Permission => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]))
    );

    return $user->assignRole($role);
}

/**
 * A super admin, for panel tests that are not about authorisation themselves.
 * Shield intercepts the gate for this role, so no permissions need to exist.
 */
function panelAdminUser(): User
{
    return User::factory()->create()->assignRole(
        Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
    );
}
