<?php

declare(strict_types=1);

use He4rt\Identity\Filament\Admin\Resources\Users\Pages\ListUsers;
use He4rt\Identity\User\Models\User;

it('renders the list of users', function (): void {
    $users = User::factory()->count(5)->create();

    $this->livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});
