<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\DTOs;

use Illuminate\Support\Facades\Date;

final readonly class MatchedInviteDTO
{
    public function __construct(
        public string $code,
        public string $channel,
        public string $inviter,
        public string $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $invite
     */
    public static function fromDiscordApi(array $invite): self
    {
        return new self(
            code: $invite['code'],
            channel: $invite['channel']['name'] ?? 'unknown',
            inviter: $invite['inviter']['username'] ?? 'unknown',
            createdAt: isset($invite['created_at'])
                ? Date::parse($invite['created_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i')
                : '',
        );
    }
}
