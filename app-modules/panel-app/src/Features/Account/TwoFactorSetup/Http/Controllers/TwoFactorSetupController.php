<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\TwoFactorSetup\Http\Controllers;

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page confirms the code through Fortify, which redirects back here. Then the user goes on through a full
 * page visit, because the page they asked for can be in the admin.
 */
final readonly class TwoFactorSetupController
{
    public function __invoke(#[CurrentUser] User $user, AuthenticationSettings $settings): Response|InertiaResponse
    {
        if (!$settings->requiresTwoFactorSetup($user)) {
            return Inertia::location(redirect()->intended(route('panel-app::dashboard'))->getTargetUrl());
        }

        return Inertia::render('two-factor-setup/index');
    }
}
