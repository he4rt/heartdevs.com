<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use He4rt\Identity\User\Models\User;

final class TwitchScopes
{
    /**
     * @return array<int, string>
     */
    public static function requestedFor(string $panel, ?User $user): array
    {
        $connectsAsStreamer = $panel !== 'admin' && $user?->can('use-streamer-tools') === true;

        $scopeSet = $connectsAsStreamer ? 'streamer' : $panel;

        $scopes = config('services.twitch.scopes.'.$scopeSet, config('services.twitch.scopes.app'));

        return is_string($scopes) && $scopes !== '' ? explode(' ', $scopes) : [];
    }
}
