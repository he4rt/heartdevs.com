<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationDiscord\Enums\RoleHistoryAction;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discord_member_id
 * @property int $discord_role_id
 * @property RoleHistoryAction $action
 * @property CarbonInterface $occurred_at
 * @property int|null $source_event_log_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read DiscordMember $member
 * @property-read DiscordRole $role
 * @property-read DiscordEventLog|null $sourceEventLog
 */
#[Table(name: 'discord_member_role_history')]
final class DiscordMemberRoleHistory extends Model
{
    /**
     * @return BelongsTo<DiscordMember, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(DiscordMember::class, 'discord_member_id');
    }

    /**
     * @return BelongsTo<DiscordRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(DiscordRole::class, 'discord_role_id');
    }

    /**
     * @return BelongsTo<DiscordEventLog, $this>
     */
    public function sourceEventLog(): BelongsTo
    {
        return $this->belongsTo(DiscordEventLog::class, 'source_event_log_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => RoleHistoryAction::class,
            'occurred_at' => 'datetime',
        ];
    }
}
