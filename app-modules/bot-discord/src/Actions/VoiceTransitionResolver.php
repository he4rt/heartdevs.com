<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\BotDiscord\ValueObjects\VoiceTransition;

final class VoiceTransitionResolver
{
    /**
     * @return list<VoiceTransition>
     */
    public function resolve(?string $oldChannelId, ?string $newChannelId): array
    {
        if ($oldChannelId === $newChannelId) {
            return []; // covers null === null AND same-channel no-op
        }

        if ($oldChannelId === null) {
            return [VoiceTransition::joined($newChannelId)];
        }

        if ($newChannelId === null) {
            return [VoiceTransition::left($oldChannelId)];
        }

        return [
            VoiceTransition::left($oldChannelId),
            VoiceTransition::joined($newChannelId),
        ];
    }
}
