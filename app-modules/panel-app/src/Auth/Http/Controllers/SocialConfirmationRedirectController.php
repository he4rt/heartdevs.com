<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Controllers;

use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\PanelApp\Auth\Http\Requests\SocialConfirmationRedirectRequest;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends a user without a password to the provider to confirm it is them. A GET redirect, so it is not a resource method.
 */
final readonly class SocialConfirmationRedirectController
{
    public function __invoke(SocialConfirmationRedirectRequest $request, SocialProvider $provider, SocialUserResolver $resolver): RedirectResponse
    {
        return $resolver->redirect($provider, route('panel-app::social-confirmation.callback', $provider));
    }
}
