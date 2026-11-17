<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Data;

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use Spatie\LaravelData\Data;

final class AuthenticationSettingsData extends Data
{
    /**
     * @param  array<int, string>  $socialProviders  the enabled providers that have credentials, so a save never sends one that cannot work
     */
    public function __construct(
        public bool $twoFactorEnabled,
        public bool $passkeysEnabled,
        public array $socialProviders,
    ) {}

    public static function fromSettings(AuthenticationSettings $settings, AvailableSocialProviders $providers): self
    {
        return new self(
            twoFactorEnabled: $settings->twoFactorEnabled,
            passkeysEnabled: $settings->passkeysEnabled,
            socialProviders: collect($providers->enabled())->map(fn (SocialProvider $provider): string => $provider->value)->values()->all(),
        );
    }
}
