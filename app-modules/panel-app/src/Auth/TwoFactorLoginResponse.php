<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * A full page visit, because the intended page can be in the admin, which runs in another bundle.
 */
final class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /** @param  Request  $request */
    public function toResponse($request): Response
    {
        $intendedUrl = redirect()->intended(Fortify::redirects('login'))->getTargetUrl();

        return Inertia::location($intendedUrl);
    }
}
