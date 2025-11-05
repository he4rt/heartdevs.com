<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use He4rt\User\Filament\Admin\Resources\Users\Pages\CreateUser;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Filament\Admin\Resources\Users\Pages\ListUsers;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;
use He4rt\User\Models\User;

beforeEach(function (): void {
    $user = User::factory()->create();
    Filament::setCurrentPanel('admin');
    $this->actingAs($user);
});

it('can register resources', function (): void {
    expect(Filament::getResources())
        ->toContain(UserResource::class);
});

it('renders the list of users', function (): void {
    $users = User::factory()->count(5)->create();

    $this->livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('can load the page', function (): void {
    $this->livewire(CreateUser::class)
        ->assertOk();
});

it('can create a user', function (): void {
    $this->livewire(CreateUser::class)
        ->fillForm([
            'username' => 'newuser',
            'email' => 'emailmaneiro@example.com',
            'password' => 'password',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(User::class, [
        'username' => 'newuser',
        'email' => 'emailmaneiro@example.com',
    ]);
});

it('can load the page edit page', function (): void {
    $user = User::factory()->create();

    $this->livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'username' => $user->username,
            'email' => $user->email,
        ]);
});

it('can update a user', function (): void {
    $user = User::factory()->create();

    $newUserData = User::factory()->make();

    $this->livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->fillForm([
            'username' => $newUserData->username,
            'email' => $newUserData->email,
        ])
        ->call('save')
        ->assertNotified();

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'username' => $newUserData->username,
        'email' => $newUserData->email,
    ]);
});

it('can delete a user', function (): void {
    $user = User::factory()->create();

    $this->livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseMissing($user);
});
