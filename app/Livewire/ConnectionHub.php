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
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConnectionHub extends Component
{
    /** @return Collection<int, ExternalIdentity> */
    #[Computed]
    public function userProviders(): Collection
    {
        return auth()->user()->providers()->where('tenant_id', filament()->getTenant()->getKey())->get();
    }

    public function render(): View
    {
        return view('livewire.connection-hub', [
            'userProviders' => $this->userProviders(),
            'supportedProviders' => IdentityProvider::supportedProviders(),
            'panel' => filament()->getCurrentPanel()->getId(),
        ]);
    }

    public function connect(IdentityProvider $provider): void
    {
        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();
        $panel = filament()->getCurrentPanel()->getId();

        session()->put('tenant', $tenant->slug);
        $state = new OAuthStateDTO(panel: $panel, tenant: $tenant->slug);
        $redirectUri = $provider->getClient()->redirectUrl($state);

        $this->redirect($redirectUri);
    }

    public function disconnect(IdentityProvider $provider): void
    {
        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        $identity = auth()->user()
            ->providers()
            ->where('tenant_id', $tenant->getKey())
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

        unset($this->userProviders);

        Notification::make()
            ->title($provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }
}
