<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the settings turn two factor on, a user without it sets it up before any other page of either panel.
 * A super admin who impersonates the user is not asked, because the authenticator app would be theirs.
 */
final readonly class RequireTwoFactorSetup
{
    public const string SETUP_ROUTE = 'panel-app::two-factor.setup';

    public function __construct(
        private AuthenticationSettings $settings,
        private Impersonation $impersonation,
    ) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $mustSetUpTwoFactor = $user instanceof User
            && $this->settings->requiresTwoFactorSetup($user)
            && !$this->impersonation->isActive();

        if (!$mustSetUpTwoFactor) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::location(route(self::SETUP_ROUTE));
    }
}
