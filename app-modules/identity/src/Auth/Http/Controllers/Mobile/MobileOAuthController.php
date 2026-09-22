<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers\Mobile;

use App\Contracts\OAuthClientContract;
use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\Actions\IssueMobileExchangeCodeAction;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
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

            return redirect()->to($this->deepLink('error', 'client_not_configured'));
        }

        throw_unless($client instanceof OAuthClientContract, NotFoundHttpException::class);

        // returnUrl só precisa ser não-nulo: MobileOAuthController::callback() monta o
        // próprio redirect de deep link e nunca lê $result->redirectUrl.
        $state = new OAuthStateDTO(
            intent: OAuthIntent::Login,
            provider: $identityProvider,
            panel: 'mobile',
            returnUrl: 'mobile',
        );

        return redirect()->to($client->redirectUrl($state));
    }

    public function callback(string $provider, HandleOAuthCallbackAction $action, IssueMobileExchangeCodeAction $issueCode): RedirectResponse
    {
        $identityProvider = $this->resolveSupportedProvider($provider);

        $state = OAuthStateDTO::fromEncryptedString((string) request()->input('state'));

        $code = request()->input('code');
        $oauthDenied = $code === null || request()->has('error');

        if ($oauthDenied) {
            return redirect()->to($this->deepLink('error', 'access_denied'));
        }

        try {
            $result = $action->execute($state, $identityProvider, $code);
        } catch (OAuthFlowException $oAuthFlowException) {
            Log::warning('Mobile OAuth flow failed', ['provider' => $provider, 'error' => $oAuthFlowException->getMessage()]);

            return redirect()->to($this->deepLink('error', 'oauth_flow_failed'));
        }

        $exchangeCode = $issueCode->execute($result->user);

        return redirect()->to($this->deepLink('callback', code: $exchangeCode));
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

    private function deepLink(string $path, ?string $error = null, ?string $code = null): string
    {
        $scheme = config('services.he4rt_app.deeplink_scheme');
        $query = array_filter(['error' => $error, 'code' => $code]);

        return sprintf('%s://oauth/%s%s', $scheme, $path, $query === [] ? '' : '?'.http_build_query($query));
    }
}
