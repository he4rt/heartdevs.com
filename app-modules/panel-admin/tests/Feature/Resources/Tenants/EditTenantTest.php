<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Pages\EditTenant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    $tenant = Tenant::query()->first();

    livewire(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertOk();
});
