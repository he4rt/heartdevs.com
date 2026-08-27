<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Queries;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Activity\Voice\ValueObjects\CurrentVoiceChannel;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

/**
 * Resolves where a user currently is in voice, reading the user's most recent
 * row from the append-only voice_messages log.
 *
 * Any row whose state is not `left` means the user is present in that channel
 * (covers presence `joined` as well as the XP ticker's `muted`/`unmuted`). This
 * is durable across both gateway reconnects and deploys, unlike discord-php's
 * `$oldState` cache.
 */
final readonly class GetCurrentVoiceChannel
{
    public function handle(IdentityProvider $provider, string $externalAccountId): ?CurrentVoiceChannel
    {
        $latest = Voice::query()
            ->join('external_identities', 'external_identities.id', '=', 'voice_messages.external_identity_id')
            ->where('external_identities.external_account_id', $externalAccountId)
            ->where('external_identities.provider', $provider->value)
            ->orderByDesc('voice_messages.id')
            ->first(['voice_messages.state', 'voice_messages.channel_id', 'voice_messages.channel_name']);

        if ($latest === null) {
            return null;
        }

        if ($latest->state === VoicePresenceEnum::Left->value) {
            return null;
        }

        if ($latest->channel_id === null) {
            return null;
        }

        return new CurrentVoiceChannel($latest->channel_id, $latest->channel_name);
    }
}
