<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Notifications\Notification;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class ConnectionHub extends Component
{
    public string $panel = 'app';

    public int $tenantId = 0;

    public function mount(): void
    {
        $this->panel = filament()->getCurrentPanel()?->getId() ?? 'app';
        $this->tenantId = filament()->getTenant()?->getKey() ?? 0;
    }

    public function render(): View
    {
        $supportedProviders = IdentityProvider::supportedProviders();

        if ($this->panel === 'admin') {
            return view('livewire.connection-hub-admin', [
                'tenantProviders' => $this->getTenantProviders(),
                'supportedProviders' => $supportedProviders,
                'panel' => $this->panel,
            ]);
        }

        return view('livewire.connection-hub', [
            'userProviders' => $this->getUserProviders(),
            'supportedProviders' => $supportedProviders,
            'panel' => $this->panel,
        ]);
    }

    public function connect(IdentityProvider $provider): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        session()->put('tenant', $tenant->slug);
        $state = new OAuthStateDTO(panel: $this->panel, tenant: $tenant->slug);
        $redirectUri = $provider->getClient()->redirectUrl($state);

        $this->redirect($redirectUri);
    }

    public function disconnect(IdentityProvider $provider): void
    {
        $identity = auth()->user()
            ->providers()
            ->where('tenant_id', $this->tenantId)
            ->where('provider', $provider->value)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('No active connection found for '.$provider->getLabel())
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    public function disconnectById(string $identityId): void
    {
        $identity = ExternalIdentity::query()
            ->where('id', $identityId)
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('Connection not found')
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($identity->provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getUserProviders(): Collection
    {
        return auth()->user()->providers()->where('tenant_id', $this->tenantId)->get();
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getTenantProviders(): Collection
    {
        return ExternalIdentity::query()
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->with('user')
            ->get();
    }
}
