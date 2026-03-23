<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Models\Sponsor;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\Sponsors\Pages\CreateSponsor;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
    $this->tenant = Tenant::query()->first();
});

it('can render', function (): void {
    livewire(CreateSponsor::class)->assertOk();
});

it('can create a sponsor', function (): void {
    livewire(CreateSponsor::class)
        ->fillForm([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Test Sponsor',
            'homepage_url' => 'https://example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Sponsor::class, [
        'name' => 'Test Sponsor',
    ]);
});
