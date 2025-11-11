<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Models\User;

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
