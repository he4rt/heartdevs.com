<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;

final class TwitchScopes
{
    /**
     * @param  array<int, TwitchStreamerFeature>  $features
     * @return array<int, string>
     */
    public static function requestedFor(string $panel, ?User $user, array $features = []): array
    {
        $baseScopes = self::configuredScopes($panel === 'admin' ? 'admin' : 'app');
        $connectsAsStreamer = $panel !== 'admin' && $user instanceof User && $user->can('use-streamer-tools');

        if (!$connectsAsStreamer) {
            return $baseScopes;
        }

        $chosenFeatures = $features === [] ? [TwitchStreamerFeature::Alerts] : $features;
        $featureScopes = array_merge(...array_map(fn (TwitchStreamerFeature $feature): array => $feature->scopes(), $chosenFeatures));

        return array_values(array_unique([
            ...$baseScopes,
            ...self::alreadyGranted($user),
            ...$featureScopes,
        ]));
    }

    /**
     * @return array<int, string>
     */
    private static function configuredScopes(string $scopeSet): array
    {
        $scopes = config('services.twitch.scopes.'.$scopeSet);

        return is_string($scopes) && $scopes !== '' ? explode(' ', $scopes) : [];
    }

    /**
     * @return array<int, string>
     */
    private static function alreadyGranted(User $user): array
    {
        $grantedScopes = $user->providers()
            ->where('provider', IdentityProvider::Twitch)
            ->activelyConnected()
            ->latest('connected_at')
            ->first()
            ?->metadata['granted_scopes'] ?? [];

        return is_array($grantedScopes) ? array_values(array_filter($grantedScopes, is_string(...))) : [];
    }
}
