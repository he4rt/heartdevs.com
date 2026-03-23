<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\PanelAdmin\Filament\Resources\Tenants\TenantResource;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('is registered in admin panel', function (): void {
    expect(Filament::getResources())
        ->toContain(TenantResource::class);
});
