<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Observers;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Sync\Handlers\HandleAuditLogEntry;
use He4rt\IntegrationDiscord\Sync\Handlers\HandleMemberAdd;
use He4rt\IntegrationDiscord\Sync\Handlers\HandleMemberRemove;
use He4rt\IntegrationDiscord\Sync\Handlers\HandleMemberUpdate;
use He4rt\IntegrationDiscord\Sync\Handlers\HandleVoiceStateUpdate;

final class DiscordEventLogObserver
{
    /** @var array<string, class-string> */
    private const array HANDLER_MAP = [
        'GUILD_MEMBER_ADD' => HandleMemberAdd::class,
        'GUILD_MEMBER_REMOVE' => HandleMemberRemove::class,
        'GUILD_MEMBER_UPDATE' => HandleMemberUpdate::class,
        'GUILD_AUDIT_LOG_ENTRY_CREATE' => HandleAuditLogEntry::class,
        'VOICE_STATE_UPDATE' => HandleVoiceStateUpdate::class,
    ];

    public function created(DiscordEventLog $eventLog): void
    {
        $handlerClass = self::HANDLER_MAP[$eventLog->event_type] ?? null;

        if ($handlerClass === null) {
            return;
        }

        resolve($handlerClass)->handle($eventLog);
    }
}
