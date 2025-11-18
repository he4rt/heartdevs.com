<?php

declare(strict_types=1);

namespace App\Livewire;

use He4rt\Authentication\Enums\OAuthProviderEnum;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConnectionHub extends Component
{
    #[Computed]
    public function userProviders()
    {
        return auth()->user()->providers()->where('tenant_id', filament()->getTenant()->getKey())->get();
    }

    public function render(): View
    {
        return view('livewire.connection-hub', [
            'userProviders' => $this->userProviders(),
            'supportedProviders' => OAuthProviderEnum::cases(),
        ]);
    }

    public function connect(OAuthProviderEnum $provider): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        session()->put('tenant', $tenant->slug);
        $redirectUri = $provider->getClient()->redirectUrl($tenant->slug);

        return redirect()->away($redirectUri);
    }
}
