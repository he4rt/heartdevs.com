<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Pages\CreateTenant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    livewire(CreateTenant::class)->assertOk();
});

it('can create a tenant', function (): void {
    $owner = User::factory()->create();

    livewire(CreateTenant::class)
        ->fillForm([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'owner_id' => $owner->getKey(),
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Tenant::class, [
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateTenant::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'name is required' => ['name', '', 'required'],
    'slug is required' => ['slug', '', 'required'],
]);
