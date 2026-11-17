<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers;

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Features\Account\Settings\Data\PasskeyData;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\EditSecurityRequest;
use He4rt\PanelApp\Features\Account\Settings\Queries\SocialConnections;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SecurityController
{
    /**
     * ensureStateIsValid() turns off a two factor setup the user left unconfirmed in an earlier session.
     */
    public function edit(EditSecurityRequest $request, #[CurrentUser] User $user, AuthenticationSettings $settings, SocialConnections $socialConnections): Response
    {
        $request->ensureStateIsValid();

        return Inertia::render('settings/security', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'twoFactorAvailable' => $settings->twoFactorEnabled,
            'passkeysAvailable' => $settings->passkeysEnabled,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'passkeys' => $user->passkeys()->latest()->get()->map(PasskeyData::fromModel(...))->values()->all(),
            'hasPassword' => $user->hasPassword(),
            'socialConnections' => $socialConnections->for($user),
        ]);
    }
}
