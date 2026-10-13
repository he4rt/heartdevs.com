<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\Health\CheckTwitchSubscriptions;
use He4rt\IntegrationTwitch\Health\CheckWebhookAddress;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Builder;

final class StreamingHealthBadge
{
    public const string TOOLTIP = 'A integração com a Twitch precisa de conserto';

    public static function label(): ?string
    {
        $errors = once(self::errors(...));

        return $errors > 0 ? (string) $errors : null;
    }

    private static function errors(): int
    {
        $user = auth()->user();
        $usesStreamerTools = $user instanceof User && $user->can('use-streamer-tools');

        if (!$usesStreamerTools) {
            return 0;
        }

        $twitchSource = StreamerSource::query()
            ->whereHas('streamer', fn (Builder $streamer): Builder => $streamer->whereBelongsTo($user))
            ->whereHas('identity', fn (Builder $identity): Builder => $identity->where('provider', IdentityProvider::Twitch)->activelyConnected())
            ->with(['identity', 'streamer'])
            ->first();

        if (!$twitchSource instanceof StreamerSource) {
            return 0;
        }

        $checksWithoutNetwork = [
            resolve(CheckWebhookAddress::class)->handle(),
            resolve(CheckTwitchSubscriptions::class)->handle($twitchSource),
        ];

        return collect($checksWithoutNetwork)
            ->filter(fn (HealthCheck $check): bool => $check->status === HealthStatus::Error)
            ->count();
    }
}
