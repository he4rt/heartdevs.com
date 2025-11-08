<?php

declare(strict_types=1);

namespace App\Livewire;

use He4rt\Authentication\Enums\OAuthProviderEnum;
use Illuminate\Contracts\View\View;
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

    public function connect(OAuthProviderEnum $provider)
    {
        session()->put('tenant', filament()->getTenant()->slug);
        $redirectUri = $provider->getClient()->redirectUrl(filament()->getTenant()->slug);

        return redirect()->away($redirectUri);
    }
}
