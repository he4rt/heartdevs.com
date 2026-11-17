<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Controllers;

use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\PanelApp\Auth\Http\Requests\SocialLoginRedirectRequest;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends the guest to the provider. A GET redirect, so it is not a resource method.
 */
final readonly class SocialLoginRedirectController
{
    public function __invoke(SocialLoginRedirectRequest $request, SocialProvider $provider, SocialUserResolver $resolver): RedirectResponse
    {
        return $resolver->redirect($provider, route('panel-app::social-login.callback', $provider));
    }
}
