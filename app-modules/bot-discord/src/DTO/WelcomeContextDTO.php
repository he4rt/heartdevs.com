<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

use Discord\Parts\User\Member;

final readonly class WelcomeContextDTO
{
    public function __construct(
        public string $userId,
        public string $guildId,
        public string $username,
        public ?string $avatarUrl,
        public ?string $serverIconUrl,
    ) {}

    public static function fromMember(Member $member, ?string $serverIconUrl): self
    {
        return new self(
            userId: (string) $member->user?->id,
            guildId: (string) $member->guild_id,
            username: (string) $member->user?->username,
            avatarUrl: $member->user?->avatar,
            serverIconUrl: $serverIconUrl,
        );
    }

    public function mention(): string
    {
        return sprintf('<@%s>', $this->userId);
    }
}
