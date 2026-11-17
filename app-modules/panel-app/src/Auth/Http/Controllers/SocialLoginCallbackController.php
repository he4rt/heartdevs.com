<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Controllers;

use DomainException;
use He4rt\Identity\SocialAccounts\Actions\SignInWithSocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\PanelApp\Auth\Http\Requests\SocialLoginCallbackRequest;
use He4rt\PanelApp\Auth\Socialite\SocialLoginResponse;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Features;

/**
 * The provider sends the guest back here with a GET, so the callback is not a resource method.
 */
final readonly class SocialLoginCallbackController
{
    public function __invoke(SocialLoginCallbackRequest $request, SocialProvider $provider, SocialUserResolver $resolver, SignInWithSocialAccount $signIn, SocialLoginResponse $response): RedirectResponse
    {
        try {
            $user = $signIn->handle(
                provider: $provider,
                socialUser: $resolver->resolve($provider, route('panel-app::social-login.callback', $provider)),
                canRegister: Features::enabled(Features::registration()),
            );
        } catch (DomainException $domainException) {
            return to_route('login')->withErrors(['social' => $domainException->getMessage()]);
        }

        return $response->for($request, $user);
    }
}
