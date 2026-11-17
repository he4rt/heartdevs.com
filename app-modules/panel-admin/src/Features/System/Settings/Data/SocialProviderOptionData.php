<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Data;

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class SocialProviderOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        public bool $configured,
        public string $credentialKeys,
        public string $callbackUrl,
    ) {}

    /** @return array<int, self> */
    public static function listFrom(AvailableSocialProviders $providers): array
    {
        $configured = $providers->configured();

        return collect(SocialProvider::cases())
            ->map(fn (SocialProvider $provider): self => new self(
                value: $provider->value,
                label: $provider->getLabel(),
                configured: in_array($provider, $configured, strict: true),
                credentialKeys: sprintf('%1$s_CLIENT_ID and %1$s_CLIENT_SECRET', Str::upper($provider->value)),
                callbackUrl: url(config()->string(sprintf('services.%s.redirect', $provider->value))),
            ))
            ->values()
            ->all();
    }
}
