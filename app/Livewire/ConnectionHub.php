<?php

declare(strict_types=1);

namespace App\Livewire;

use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
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
            'supportedProviders' => IdentityProvider::cases(),
        ]);
    }

    public function connect(IdentityProvider $provider): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        session()->put('tenant', $tenant->slug);
        $state = new OAuthStateDTO(panel: 'user', tenant: $tenant->slug);
        $redirectUri = $provider->getClient()->redirectUrl($state);

        return redirect()->away($redirectUri);
    }
}
