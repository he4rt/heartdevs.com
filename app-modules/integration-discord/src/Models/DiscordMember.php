<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\Carbon;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDiscord\Database\Factories\DiscordMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $discord_guild_id
 * @property string $discord_user_id
 * @property string|null $external_identity_id
 * @property string $username
 * @property string|null $global_name
 * @property string|null $avatar
 * @property string|null $nickname
 * @property bool $is_bot
 * @property bool $is_pending
 * @property Carbon|null $joined_at
 * @property Carbon|null $premium_since
 * @property Carbon|null $communication_disabled_until
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscordGuild $guild
 * @property-read ExternalIdentity|null $externalIdentity
 * @property-read Collection<int, DiscordRole> $roles
 * @property-read Collection<int, DiscordMemberRoleHistory> $roleHistory
 */
#[Fillable([
    'discord_guild_id',
    'discord_user_id',
    'external_identity_id',
    'username',
    'global_name',
    'avatar',
    'nickname',
    'is_bot',
    'is_pending',
    'joined_at',
    'premium_since',
    'communication_disabled_until',
    'left_at',
])]
final class DiscordMember extends Model
{
    /** @use HasFactory<DiscordMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DiscordGuild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(DiscordGuild::class, 'discord_guild_id');
    }

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function externalIdentity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class);
    }

    /**
     * @return BelongsToMany<DiscordRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(DiscordRole::class, 'discord_member_roles')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<DiscordMemberRoleHistory, $this>
     */
    public function roleHistory(): HasMany
    {
        return $this->hasMany(DiscordMemberRoleHistory::class);
    }

    protected static function newFactory(): DiscordMemberFactory
    {
        return DiscordMemberFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'is_pending' => 'boolean',
            'joined_at' => 'datetime',
            'premium_since' => 'datetime',
            'communication_disabled_until' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
