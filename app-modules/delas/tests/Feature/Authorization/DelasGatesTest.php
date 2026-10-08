<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Spatie\Permission\Models\Role;

test('os papéis da He4rt Delas existem depois das migrations, sem seed', function (UserRole $role): void {
    expect(Role::findByName($role->value, UserRole::GUARD))->toBeInstanceOf(Role::class);
})->with([UserRole::DelasModerator, UserRole::DelasLead]);

test('cada papel passa só nos gates do seu nível', function (?string $state, bool $moderate, bool $lead): void {
    $factory = User::factory();
    $user = ($state === null ? $factory : $factory->{$state}())->create();

    expect($user->can('moderate-delas'))->toBe($moderate);
    expect($user->can('lead-delas'))->toBe($lead);
})->with([
    'membra comum' => [null, false, false],
    'moderadora' => ['delasModerator', true, false],
    'líder' => ['delasLead', true, true],
    'super admin' => ['superAdmin', true, true],
]);

test('ao perder o papel, a moderadora perde o gate na próxima leitura', function (): void {
    $user = User::factory()->delasModerator()->create();

    $user->removeRole(UserRole::DelasModerator);

    expect($user->fresh()->can('moderate-delas'))->toBeFalse();
});
