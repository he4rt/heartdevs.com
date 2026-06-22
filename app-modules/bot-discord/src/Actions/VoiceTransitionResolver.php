<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\Activity\Voice\ValueObjects\VoiceTransition;

final class VoiceTransitionResolver
{
    /**
     * Derive the presence transitions of a single voice-state change.
     *
     * Pure: ids and names in, transitions out — no gateway or DB access, so the
     * full truth table stays trivially testable. Names are paired with their
     * presence here (joined → new channel, left → old channel).
     *
     * A channel id can arrive WITHOUT its name. The id is a raw snowflake from
     * the gateway payload (always present), but the name is a cache lookup
     * (`$discord->getChannel($id)?->name`) that returns null when the channel
     * isn't cached yet — e.g. a just-created dynamic voice channel, or a
     * VOICE_STATE_UPDATE landing mid channel-cache rebuild after a reconnect.
     * Since `VoiceTransition::channelName` is a non-null string, we fall back to
     * the snowflake id: a row is never nameless, just occasionally id-named.
     *
     * @return list<VoiceTransition>
     */
    public function resolve(
        ?string $oldChannelId,
        ?string $oldChannelName,
        ?string $newChannelId,
        ?string $newChannelName,
    ): array {
        if ($oldChannelId === $newChannelId) {
            return []; // covers null === null AND same-channel no-op
        }

        if ($oldChannelId === null) {
            return [VoiceTransition::joined($newChannelId, $newChannelName ?? $newChannelId)];
        }

        if ($newChannelId === null) {
            return [VoiceTransition::left($oldChannelId, $oldChannelName ?? $oldChannelId)];
        }

        return [
            VoiceTransition::left($oldChannelId, $oldChannelName ?? $oldChannelId),
            VoiceTransition::joined($newChannelId, $newChannelName ?? $newChannelId),
        ];
    }
}
