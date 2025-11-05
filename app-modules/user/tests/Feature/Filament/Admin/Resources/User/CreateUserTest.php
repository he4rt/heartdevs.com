<?php

declare(strict_types=1);

use He4rt\User\Filament\Admin\Resources\Users\Pages\CreateUser;
use He4rt\User\Models\User;

it('can load the page', function (): void {
    $this->livewire(CreateUser::class)
        ->assertOk();
});

it('can create a user', function (): void {
    $this->livewire(CreateUser::class)
        ->fillForm([
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'emailmaneiro@example.com',
            'password' => 'password',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(User::class, [
        'username' => 'newuser',
        'name' => 'New User',
        'email' => 'emailmaneiro@example.com',
    ]);
});
