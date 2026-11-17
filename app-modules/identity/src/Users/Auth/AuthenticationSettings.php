<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Auth;

use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Spatie\LaravelSettings\Settings;

/**
 * The authentication methods a super admin turns on and off. A method that is off keeps the user data.
 */
final class AuthenticationSettings extends Settings
{
    public bool $twoFactorEnabled;

    public bool $passkeysEnabled;

    /** @var array<int, string> the values of the enabled SocialProvider cases */
    public array $socialProviders;

    public static function group(): string
    {
        return 'authentication';
    }

    public function socialProviderEnabled(SocialProvider $provider): bool
    {
        return in_array($provider->value, $this->socialProviders, strict: true);
    }

    public function challengesTwoFactor(User $user): bool
    {
        return $this->twoFactorEnabled && $user->hasEnabledTwoFactorAuthentication();
    }

    public function requiresTwoFactorSetup(User $user): bool
    {
        return $this->twoFactorEnabled && !$user->hasEnabledTwoFactorAuthentication();
    }
}
