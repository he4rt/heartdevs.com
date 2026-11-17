<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create();
    $this->target = User::factory()->create(['name' => 'Target']);

    actingAs($this->admin);

    $this->users = fn (): array => DB::table('identity_users')->orderBy('id')->get()->all();
});

it('refuses each endpoint without its permission and changes nothing', function (PermissionsEnum $permission, string $method, string $routeName, bool $onTarget, array $payload): void {
    $this->admin->roles()->first()?->revokePermissionTo($permission->buildPermissionFor(User::class));
    $this->admin->forgetCachedPermissions();
    $before = ($this->users)();

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::'.$routeName, $onTarget ? $this->target : []), $payload)
        ->assertForbidden();

    expect(($this->users)())->toEqual($before);
})->with([
    'list' => [PermissionsEnum::ViewAny, 'GET', 'users.index', false, []],
    'lookup' => [PermissionsEnum::ViewAny, 'GET', 'users.lookup', false, []],
    'create' => [PermissionsEnum::Create, 'POST', 'users.store', false, ['name' => 'Ada', 'email' => 'ada@he4rt.dev', 'password' => 'Str0ng!Passw0rd']],
    'update' => [PermissionsEnum::Update, 'PUT', 'users.update', true, ['name' => 'Changed', 'email' => 'changed@he4rt.dev']],
    'delete' => [PermissionsEnum::Delete, 'DELETE', 'users.destroy', true, []],
]);

it('answers 404 for an impersonation while no app receives the user', function (): void {
    config(['he4rt.impersonation.redirect_to' => null]);

    $this->withExceptionHandling()
        ->post(route('panel-admin::users.impersonate', $this->target))
        ->assertNotFound();

    expect(auth()->user()?->is($this->admin))->toBeTrue();
});
