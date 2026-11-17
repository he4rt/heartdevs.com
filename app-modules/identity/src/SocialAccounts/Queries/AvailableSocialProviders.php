<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Queries;

use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;

/**
 * The one place that asks whether a provider has credentials (ADR 0001).
 */
final readonly class AvailableSocialProviders
{
    public function __construct(private AuthenticationSettings $settings) {}

    /** @return array<int, SocialProvider> */
    public function configured(): array
    {
        return collect(SocialProvider::cases())
            ->filter(fn (SocialProvider $provider): bool => filled(config(sprintf('services.%s.client_id', $provider->value)))
                && filled(config(sprintf('services.%s.client_secret', $provider->value))))
            ->values()
            ->all();
    }

    /** @return array<int, SocialProvider> */
    public function enabled(): array
    {
        return collect($this->configured())
            ->filter(fn (SocialProvider $provider): bool => $this->settings->socialProviderEnabled($provider))
            ->values()
            ->all();
    }

    public function isEnabled(SocialProvider $provider): bool
    {
        return in_array($provider, $this->enabled(), strict: true);
    }
}
