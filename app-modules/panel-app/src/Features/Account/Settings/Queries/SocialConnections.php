<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Queries;

use App\Data\Support\EnumOptionData;
use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Features\Account\Settings\Data\SocialConnectionData;

/**
 * The enabled providers, plus a connected account of a provider that is off now, so it can still be disconnected.
 */
final readonly class SocialConnections
{
    public function __construct(private AvailableSocialProviders $providers) {}

    /** @return array<int, SocialConnectionData> */
    public function for(User $user): array
    {
        $accounts = $user->socialAccounts()->get()->keyBy(fn (SocialAccount $account): string => $account->provider->value);
        $enabled = $this->providers->enabled();

        return collect(SocialProvider::cases())
            ->filter(fn (SocialProvider $provider): bool => in_array($provider, $enabled, strict: true) || $accounts->has($provider->value))
            ->map(function (SocialProvider $provider) use ($accounts, $user): SocialConnectionData {
                $account = $accounts->get($provider->value);

                return new SocialConnectionData(
                    provider: EnumOptionData::fromEnum($provider),
                    accountId: $account?->id,
                    connectedAt: $account?->created_at?->toIso8601String(),
                    canDisconnect: $account instanceof SocialAccount && $user->hasSignInMethodBesides($account),
                );
            })
            ->values()
            ->all();
    }
}
