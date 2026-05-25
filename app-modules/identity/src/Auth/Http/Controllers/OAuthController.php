<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers;

use App\Contracts\OAuthClientContract;
use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OAuthController extends Controller
{
    public function getRedirect(string $tenant, string $panel, string $provider): RedirectResponse
    {
        $identityProvider = IdentityProvider::tryFrom($provider);

        throw_if($identityProvider === null, NotFoundHttpException::class);

        $client = $identityProvider->getClient();

        throw_unless($client instanceof OAuthClientContract, NotFoundHttpException::class);

        $state = new OAuthStateDTO(
            intent: Auth::check() ? OAuthIntent::Link : OAuthIntent::Login,
            provider: $identityProvider,
            panel: $panel,
            tenant: $tenant,
            returnUrl: Auth::check() ? url()->previous() : null,
        );

        return redirect()->to($client->redirectUrl($state));
    }

    public function getAuthenticate(string $provider, HandleOAuthCallbackAction $action): RedirectResponse
    {
        $identityProvider = IdentityProvider::tryFrom($provider);

        throw_if($identityProvider === null, NotFoundHttpException::class);

        $state = OAuthStateDTO::fromEncryptedString(request()->input('state'));

        $result = $action->execute($state, $identityProvider, request()->input('code'));

        if ($result->intent === OAuthIntent::Login) {
            Auth::login($result->user);
            filament()->setCurrentPanel(filament()->getPanel($state->panel));
            filament()->setTenant($result->tenant);
        }

        return redirect()->to($result->redirectUrl);
    }
}
