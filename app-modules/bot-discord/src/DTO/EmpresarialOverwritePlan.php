<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

/**
 * The permission overwrites to stamp on a channel when it becomes a Sala Empresarial:
 * deny `@everyone` and allow the resolved Partner Role for the same voice-privacy set.
 *
 * `MENTION_EVERYONE` relies on the category's existing inheritance and `VIEW_CHANNEL`
 * is intentionally left untouched (the room stays visible-but-locked), so neither
 * appears here.
 */
final readonly class EmpresarialOverwritePlan
{
    /**
     * Discord channel-permission keys that gate a private voice room.
     *
     * @var list<string>
     */
    public const array PRIVATE_ROOM_PERMISSIONS = [
        'connect',
        'speak',
        'use_vad',
        'send_messages',
        'read_message_history',
    ];

    /**
     * @param  list<string>  $denyEveryone  Permissions denied to `@everyone`.
     * @param  list<string>  $allowPartnerRole  Permissions allowed for the Partner Role.
     */
    private function __construct(
        public string $companySlug,
        public string $partnerRoleId,
        public array $denyEveryone,
        public array $allowPartnerRole,
    ) {}

    public static function for(string $companySlug, string $partnerRoleId): self
    {
        return new self(
            companySlug: $companySlug,
            partnerRoleId: $partnerRoleId,
            denyEveryone: self::PRIVATE_ROOM_PERMISSIONS,
            allowPartnerRole: self::PRIVATE_ROOM_PERMISSIONS,
        );
    }
}
