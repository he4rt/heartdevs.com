<?php

declare(strict_types=1);

namespace He4rt\User\Filament\User\Pages;

use Filament\Facades\Filament;
use He4rt\Tenant\Models\Tenant;
use Livewire\Attributes\Computed;

class Dashboard extends \Filament\Pages\Dashboard
{
    public Tenant $tenant;

    protected string $view = 'users::filament.app-dashboard';

    public function mount(): void
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $this->tenant = auth()->user()->tenants()->where('slug', '=', $tenant->slug)->first();
    }

    #[Computed]
    public function events()
    {
        return $this->tenant->events->where('active', true)->take(5);
    }

    #[Computed]
    public function stats()
    {
        return auth()->user()->character()->where('tenant_id', '=', $this->tenant->getKey())->first();
    }
}
