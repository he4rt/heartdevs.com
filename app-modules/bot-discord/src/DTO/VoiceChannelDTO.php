<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

use Carbon\CarbonInterface;

final class VoiceChannelDTO
{
    public function __construct(
        public readonly string $guildId,
        public readonly string $channelId,
        public readonly string $ownerId,
        public int $usersCount,
        /** @var list<string> */
        public array $users,
        public ?CarbonInterface $lastJoinedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            guildId: $data['guildId'],
            channelId: $data['channelId'],
            ownerId: $data['ownerId'],
            usersCount: $data['usersCount'],
            users: $data['users'],
            lastJoinedAt: $data['lastJoinedAt'] ?? null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->usersCount === 0;
    }

    public function isLongTermEmpty(): bool
    {
        return abs(now()->diffInSeconds($this->lastJoinedAt)) >= 20;
    }
}
