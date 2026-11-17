<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Auth\Http\Controllers;

use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Auth\Http\Requests\LoginRequest;
use He4rt\PanelAdmin\Auth\LoginOutcome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class LoginController
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $isSignedInSuperAdmin = $user instanceof User && $user->hasRole(Roles::SuperAdmin);

        if ($isSignedInSuperAdmin) {
            return to_route('panel-admin::home');
        }

        return Inertia::render('auth/login');
    }

    public function store(LoginRequest $request): HttpResponse
    {
        $outcome = $request->authenticate();

        if ($outcome === LoginOutcome::AwaitingSecondFactor) {
            return Inertia::location(route('two-factor.login'));
        }

        $request->session()->regenerate();
        $request->session()->passwordConfirmed();

        return Inertia::location(redirect()->intended(route('panel-admin::home', absolute: false)));
    }

    public function destroy(Request $request): HttpResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location(route('panel-admin::login'));
    }
}
