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

    /**
     * Iniciar login OAuth
     *
     * Redireciona pro provider (discord, github ou twitch). O provider
     * devolve o usuário pro callback web fixo, que redireciona de volta
     * pro app via deep link com um código de troca de uso único — ver
     * POST /api/mobile/auth/exchange.
     */
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

        $state = new OAuthStateDTO(
            intent: OAuthIntent::MobileLogin,
            provider: $identityProvider,
            panel: 'mobile',
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
