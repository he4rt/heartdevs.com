<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Concerns;

use Discord\Parts\User\Member;

trait ResolvesGuildIcon
{
    /**
     * Build the Discord CDN URL for a guild icon hash. Returns null when the
     * guild has no icon; animated hashes (`a_…`) resolve to `.gif`.
     */
    public function guildIconUrl(string $guildId, ?string $iconHash): ?string
    {
        if (blank($guildId) || blank($iconHash)) {
            return null;
        }

        $extension = str_starts_with($iconHash, 'a_') ? 'gif' : 'png';

        return sprintf(
            'https://cdn.discordapp.com/icons/%s/%s.%s',
            $guildId,
            $iconHash,
            $extension
        );
    }

    public function resolveGuildIcon(Member $member): ?string
    {
        $iconHash = $member->guild?->icon;

        return $this->guildIconUrl(
            (string) $member->guild_id,
            is_string($iconHash) ? $iconHash : null,
        );
    }
}
