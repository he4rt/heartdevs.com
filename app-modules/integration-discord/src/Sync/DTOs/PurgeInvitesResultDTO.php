<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\DTOs;

final readonly class PurgeInvitesResultDTO
{
    /**
     * @param  list<MatchedInviteDTO>  $invites
     */
    public function __construct(
        public int $total,
        public int $matched,
        public int $deleted,
        public int $failed,
        public array $invites,
    ) {}

    /**
     * @param  list<MatchedInviteDTO>  $invites
     */
    public static function fromDryRun(int $total, array $invites): self
    {
        return new self(
            total: $total,
            matched: count($invites),
            deleted: 0,
            failed: 0,
            invites: $invites,
        );
    }

    /**
     * @param  list<MatchedInviteDTO>  $invites
     */
    public static function fromPurge(int $total, array $invites, int $deleted, int $failed): self
    {
        return new self(
            total: $total,
            matched: count($invites),
            deleted: $deleted,
            failed: $failed,
            invites: $invites,
        );
    }
}
