<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\User\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as FilamentDashboard;
use He4rt\Identity\Tenant\Models\Tenant;
use Livewire\Attributes\Computed;

class Dashboard extends FilamentDashboard
{
    protected string $view = 'identity::filament.app-dashboard';

    private ?Tenant $tenant = null;

    public function mount(): void
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $this->tenant = auth()->user()->tenants()->where('slug', '=', $tenant->slug)->first();
    }

    #[Computed]
    public function stats(): mixed
    {
        return auth()->user()->character()->where('tenant_id', '=', $this->tenant->getKey())->first();
    }
}
