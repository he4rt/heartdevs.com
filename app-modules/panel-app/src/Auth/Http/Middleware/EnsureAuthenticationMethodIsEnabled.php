<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Middleware;

use Closure;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify registers its routes once, at boot. This middleware hides the routes of a method the settings turn off.
 */
final readonly class EnsureAuthenticationMethodIsEnabled
{
    public function __construct(private AuthenticationSettings $settings) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $isDisabledTwoFactorRoute = $request->routeIs('two-factor.*') && !$this->settings->twoFactorEnabled;
        $isDisabledPasskeyRoute = $request->routeIs('passkey.*') && !$this->settings->passkeysEnabled;

        abort_if($isDisabledTwoFactorRoute || $isDisabledPasskeyRoute, 404);

        return $next($request);
    }
}
