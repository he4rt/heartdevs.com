<?php

declare(strict_types=1);

use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use He4rt\Tenant\Models\Tenant;

use function Pest\Livewire\livewire;

it('renders the list of tenants', function (): void {
    $tenants = Tenant::factory()->count(5)->create();

    livewire(ListTenants::class)
        ->assertOk()
        ->assertCanSeeTableRecords($tenants);
});
