<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use He4rt\Identity\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use He4rt\Identity\Tenant\Models\Tenant;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can load the edit page', function (): void {
    $tenant = Tenant::factory()->create();

    livewire(EditTenant::class, [
        'record' => $tenant->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'owner_id' => $tenant->owner_id,
            'active' => $tenant->active,
        ]);
});

it('can update a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $newTenantData = Tenant::factory()->make();

    livewire(EditTenant::class, [
        'record' => $tenant->id,
    ])
        ->fillForm([
            'name' => $newTenantData->name,
            'slug' => $newTenantData->slug,
            'domain' => 'my-tenant.test',
            'active' => $newTenantData->active,
        ])
        ->call('save')
        ->assertNotified();

    assertDatabaseHas(Tenant::class, [
        'id' => $tenant->id,
        'name' => $newTenantData->name,
        'slug' => $newTenantData->slug,
    ]);
});

it('can delete a tenant', function (): void {
    $tenant = Tenant::factory()->create();

    livewire(EditTenant::class, [
        'record' => $tenant->id,
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    $this->assertSoftDeleted($tenant);
});
