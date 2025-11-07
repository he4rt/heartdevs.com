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
        return auth()->user()->providers;
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
        $redirectUri = $provider->getClient()->redirectUrl();

        return redirect()->away($redirectUri);
    }
}
