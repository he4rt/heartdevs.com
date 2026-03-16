<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use He4rt\Identity\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\Identity\User\Models\User;

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

    $this->livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->fillForm([
            'username' => 'fulano',
            'email' => 'fulano@email.com',
            'zip_code' => '13000-000',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'username' => 'fulano',
        'email' => 'fulano@email.com',
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
