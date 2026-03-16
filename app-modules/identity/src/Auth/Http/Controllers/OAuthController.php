<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\Actions\AuthenticateAction;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Http\RedirectResponse;

final class OAuthController extends Controller
{
    public function getRedirect(IdentityProvider $provider): RedirectResponse
    {
        return redirect()->to($provider->getClient()->redirectUrl());
    }

    public function getAuthenticate(IdentityProvider $provider, AuthenticateAction $action): RedirectResponse
    {
        $state = OAuthStateDTO::fromHashedString(request()->input('state'));

        $action->withOAuth($state, $provider, request()->input('code'));

        if ($state->tenant === null) {
            return $this->basePanelRedirectResponse($state);
        }

        if ($state->panel === 'event') {
            return $this->eventRedirectResponse($state);
        }

        $redirectUri = filament()
            ->getPanel($state->panel)
            ->getUrl(Tenant::query()->where('slug', $state->tenant)->firstOrFail());

        return redirect()->to($redirectUri);
    }

    private function eventRedirectResponse(OAuthStateDTO $state): RedirectResponse
    {

        $tenant = Tenant::query()->where('slug', $state->tenant)->firstOrFail();
        $baseUri = app()->isProduction()
            ? $tenant->domain
            : $state->tenant;

        return redirect()->intended(route('filament.event.pages.participant-dashboard', [
            'tenant' => $baseUri,
        ]));

    }

    private function basePanelRedirectResponse(OAuthStateDTO $state): RedirectResponse
    {
        $panel = filament()->getPanel($state->panel);

        return redirect()->intended($panel->getUrl());
    }
}
