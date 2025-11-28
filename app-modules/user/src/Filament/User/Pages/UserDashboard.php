<?php

declare(strict_types=1);

namespace He4rt\User\Filament\User\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as FilamentDashboard;
use He4rt\Tenant\Models\Tenant;
use Livewire\Attributes\Computed;

class UserDashboard extends FilamentDashboard
{
    protected string $view = 'users::filament.app-dashboard';

    private ?Tenant $tenant = null;

    public function mount(): void
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $this->tenant = auth()->user()->tenants()->where('slug', '=', $tenant->slug)->first();
    }

    #[Computed]
    public function stats()
    {
        return auth()->user()->character()->where('tenant_id', '=', $this->tenant?->getKey())->first();
    }
}
