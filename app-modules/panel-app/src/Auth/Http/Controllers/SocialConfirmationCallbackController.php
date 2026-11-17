<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Controllers;

use He4rt\Identity\SocialAccounts\Exceptions\SocialSignInFailed;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Auth\Http\Requests\SocialConfirmationCallbackRequest;
use He4rt\PanelApp\Auth\Socialite\SocialConfirmationResponse;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

/**
 * The provider sends the user back here with a GET, so the callback is not a resource method.
 */
final readonly class SocialConfirmationCallbackController
{
    public function __invoke(SocialConfirmationCallbackRequest $request, SocialProvider $provider, #[CurrentUser] User $user, SocialUserResolver $resolver, SocialConfirmationResponse $response): RedirectResponse
    {
        try {
            $socialUser = $resolver->resolve($provider, route('panel-app::social-confirmation.callback', $provider));
        } catch (SocialSignInFailed $socialSignInFailed) {
            return to_route('password.confirm')->withErrors(['social' => $socialSignInFailed->getMessage()]);
        }

        return $response->for($request, $user, $provider, $socialUser);
    }
}
