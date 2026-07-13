<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers;

use App\Contracts\OAuthClientContract;
use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OAuthController extends Controller
{
    public function getRedirect(string $panel, string $provider): RedirectResponse
    {
        $identityProvider = IdentityProvider::tryFrom($provider);

        throw_if($identityProvider === null, NotFoundHttpException::class);

        try {
            $client = $identityProvider->getClient();
        } catch (RuntimeException $runtimeException) {
            Log::warning('OAuth client not configured', ['provider' => $provider, 'error' => $runtimeException->getMessage()]);

            return redirect()->to('/');
        }

        throw_unless($client instanceof OAuthClientContract, NotFoundHttpException::class);

        $state = new OAuthStateDTO(
            intent: Auth::check() ? OAuthIntent::Link : OAuthIntent::Login,
            provider: $identityProvider,
            panel: $panel,
            returnUrl: Auth::check() ? url()->previous() : null,
        );

        return redirect()->to($client->redirectUrl($state));
    }

    public function getAuthenticate(string $provider, HandleOAuthCallbackAction $action): RedirectResponse
    {
        $identityProvider = IdentityProvider::tryFrom($provider);

        throw_if($identityProvider === null, NotFoundHttpException::class);

        $state = OAuthStateDTO::fromEncryptedString(request()->input('state'));

        $code = request()->input('code');
        $oauthDenied = $code === null || request()->has('error');

        if ($oauthDenied) {
            $fallbackUrl = $state->returnUrl ?? '/';

            return redirect()->to($fallbackUrl);
        }

        try {
            $result = $action->execute($state, $identityProvider, $code);
        } catch (OAuthFlowException $oAuthFlowException) {
            Log::warning('OAuth flow failed', ['provider' => $provider, 'error' => $oAuthFlowException->getMessage()]);

            return redirect()->to($state->returnUrl ?? '/');
        }

        if ($result->hasMergeConflict()) {
            session()->put('oauth_merge_pending', $result->mergeConflict->toSession());

            return redirect()->to($result->redirectUrl);
        }

        if ($result->intent === OAuthIntent::Login) {
            Auth::login($result->user);
            filament()->setCurrentPanel(filament()->getPanel($state->panel));
        }

        return redirect()->to($result->redirectUrl);
    }
}
