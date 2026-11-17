<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Socialite;

use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirms the password when the provider account that came back is one of the user's own.
 * Without an intended URL (the delete dialog), the user goes back to the profile page.
 */
final readonly class SocialConfirmationResponse
{
    public function for(Request $request, User $user, SocialProvider $provider, SocialUser $socialUser): RedirectResponse
    {
        if (!$user->ownsSocialAccount($provider, $socialUser->providerUserId)) {
            return to_route('password.confirm')->withErrors([
                'social' => sprintf('This %s account is not linked to you.', $provider->getLabel()),
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('panel-app::settings.profile.edit'));
    }
}
