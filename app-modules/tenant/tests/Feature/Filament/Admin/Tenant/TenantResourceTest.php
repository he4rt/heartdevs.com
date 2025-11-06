<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\TenantResource;

it('can register resources', function (): void {
    expect(Filament::getResources())
        ->toContain(TenantResource::class);
});
