<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Socialite;

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

/**
 * Logs the user in, or sends them to the two factor challenge, the same way Fortify's login pipeline does.
 * A fresh login confirms the password too, as MarkPasswordAsConfirmed does for a password login.
 */
final readonly class SocialLoginResponse
{
    public function __construct(private AuthenticationSettings $settings, private StatefulGuard $guard) {}

    public function for(Request $request, User $user): RedirectResponse
    {
        if ($this->settings->challengesTwoFactor($user)) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return to_route('two-factor.login');
        }

        $this->guard->login($user);
        $request->session()->regenerate();
        $request->session()->passwordConfirmed();

        return redirect()->intended(Fortify::redirects('login'));
    }
}
