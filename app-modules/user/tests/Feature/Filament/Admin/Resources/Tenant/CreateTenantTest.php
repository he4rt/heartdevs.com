<?php

declare(strict_types=1);

use He4rt\Tenant\Models\Tenant;
use He4rt\User\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use He4rt\User\Models\User;

it('can load the create page', function (): void {
    $this->livewire(CreateTenant::class)
        ->assertOk();
});

it('can create a tenant', function (): void {
    $owner = User::factory()->create();

    $this->livewire(CreateTenant::class)
        ->fillForm([
            'name' => 'My Tenant',
            'slug' => 'my-tenant',
            'owner_id' => $owner->id,
            'active' => true,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(Tenant::class, [
        'name' => 'My Tenant',
        'slug' => 'my-tenant',
        'owner_id' => $owner->id,
        'active' => true,
    ]);
});
