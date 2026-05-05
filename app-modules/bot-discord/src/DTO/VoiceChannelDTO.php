<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final class VoiceChannelDTO
{
    public function __construct(
        public readonly string $guildId,
        public readonly string $channelId,
        public readonly string $ownerId,
        public int $usersCount,
        /** @var array<string, mixed> */
        public array $users,
        public ?CarbonInterface $lastJoinedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        $lastJoinedAt = null;
        if (filled($data['lastJoinedAt'])) {
            if ($data['lastJoinedAt'] instanceof CarbonInterface) {
                $lastJoinedAt = $data['lastJoinedAt'];
            } elseif (is_string($data['lastJoinedAt'])) {
                $lastJoinedAt = Date::parse($data['lastJoinedAt']);
            }
        }

        return new self(
            guildId: $data['guildId'],
            channelId: $data['channelId'],
            ownerId: $data['ownerId'],
            usersCount: $data['usersCount'],
            users: $data['users'],
            lastJoinedAt: $lastJoinedAt,
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
