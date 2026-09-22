<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers\Mobile;

use App\Contracts\OAuthClientContract;
use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\Auth\Support\MobileOAuthDeepLink;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MobileOAuthController extends Controller
{
    /** @var array<int, IdentityProvider> */
    private const array SUPPORTED_PROVIDERS = [
        IdentityProvider::Discord,
        IdentityProvider::GitHub,
        IdentityProvider::Twitch,
    ];

    public function redirect(string $provider): RedirectResponse
    {
        $identityProvider = $this->resolveSupportedProvider($provider);

        try {
            $client = $identityProvider->getClient();
        } catch (RuntimeException $runtimeException) {
            Log::warning('Mobile OAuth client not configured', ['provider' => $provider, 'error' => $runtimeException->getMessage()]);

            return redirect()->to(MobileOAuthDeepLink::build('error', 'client_not_configured'));
        }

        throw_unless($client instanceof OAuthClientContract, NotFoundHttpException::class);

        // Discord/GitHub/Twitch têm UMA redirect_uri fixa cadastrada (o callback
        // web em /auth/oauth/{provider} — ver *OAuthClient::callbackUrl()). Por
        // isso o retorno do provider sempre bate em OAuthController::getAuthenticate,
        // nunca aqui; é ele quem finaliza o login mobile lendo intent=MobileLogin.
        $state = new OAuthStateDTO(
            intent: OAuthIntent::MobileLogin,
            provider: $identityProvider,
            panel: 'mobile',
            returnUrl: 'mobile',
        );

        return redirect()->to($client->redirectUrl($state));
    }

    private function resolveSupportedProvider(string $provider): IdentityProvider
    {
        $identityProvider = IdentityProvider::tryFrom($provider);

        throw_unless(
            $identityProvider !== null && in_array($identityProvider, self::SUPPORTED_PROVIDERS, strict: true),
            NotFoundHttpException::class,
        );

        return $identityProvider;
    }
}
