<?php

declare(strict_types=1);

use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Livewire\livewire;

it('can load the create page', function (): void {
    livewire(CreateTenant::class)
        ->assertOk();
});

it('can create a tenant', function (): void {
    $owner = User::factory()->create();

    livewire(CreateTenant::class)
        ->fillForm([
            'name' => 'My Tenant',
            'slug' => 'my-tenant',
            'domain' => 'my-tenant.test',
            'owner_id' => $owner->id,
            'active' => true,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(Tenant::class, [
        'name' => 'My Tenant',
        'slug' => 'my-tenant',
        'domain' => 'my-tenant.test',
        'owner_id' => $owner->id,
        'active' => true,
    ]);
});

test('name field should fill slug after updated ', function (): void {
    $owner = User::factory()->create();

    livewire(CreateTenant::class)
        ->fillForm([
            'name' => 'My Tenant',
            'owner_id' => $owner->id,
            'domain' => 'my-tenant.test',
            'active' => true,
        ])
        ->assertSchemaComponentStateSet('slug', 'my-tenant')
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(Tenant::class, [
        'name' => 'My Tenant',
        'slug' => 'my-tenant',
        'domain' => 'my-tenant.test',
        'owner_id' => $owner->id,
        'active' => true,
    ]);
});
