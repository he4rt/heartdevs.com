<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts;

use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts\SocialAccountRedirectRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends the user to the provider to connect an account. A GET redirect, so it is not a resource method.
 */
final readonly class SocialAccountRedirectController
{
    public function __invoke(SocialAccountRedirectRequest $request, SocialProvider $provider, SocialUserResolver $resolver): RedirectResponse
    {
        return $resolver->redirect($provider, route('panel-app::social-accounts.callback', $provider));
    }
}
