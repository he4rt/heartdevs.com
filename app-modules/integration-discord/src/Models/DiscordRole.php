<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\Carbon;
use He4rt\IntegrationDiscord\Database\Factories\DiscordRoleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $discord_guild_id
 * @property string $discord_role_id
 * @property string $name
 * @property int $color
 * @property int $position
 * @property int $permissions
 * @property bool $is_hoisted
 * @property bool $is_mentionable
 * @property bool $is_managed
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscordGuild $guild
 * @property-read Collection<int, DiscordMember> $members
 */
final class DiscordRole extends Model
{
    /** @use HasFactory<DiscordRoleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DiscordGuild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(DiscordGuild::class, 'discord_guild_id');
    }

    /**
     * @return BelongsToMany<DiscordMember, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(DiscordMember::class, 'discord_member_roles')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    protected static function newFactory(): DiscordRoleFactory
    {
        return DiscordRoleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => 'integer',
            'position' => 'integer',
            'permissions' => 'integer',
            'is_hoisted' => 'boolean',
            'is_mentionable' => 'boolean',
            'is_managed' => 'boolean',
        ];
    }
}
