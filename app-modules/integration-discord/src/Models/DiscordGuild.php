<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationDiscord\Database\Factories\DiscordGuildFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $discord_guild_id
 * @property string $name
 * @property string|null $icon
 * @property string|null $description
 * @property int|null $member_count
 * @property int $premium_tier
 * @property array<int, string> $features
 * @property CarbonInterface|null $synced_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, DiscordChannel> $channels
 * @property-read Collection<int, DiscordRole> $roles
 * @property-read Collection<int, DiscordMember> $members
 */
final class DiscordGuild extends Model
{
    /** @use HasFactory<DiscordGuildFactory> */
    use HasFactory;

    /**
     * @return HasMany<DiscordChannel, $this>
     */
    public function channels(): HasMany
    {
        return $this->hasMany(DiscordChannel::class, 'discord_guild_id');
    }

    /**
     * @return HasMany<DiscordRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(DiscordRole::class, 'discord_guild_id');
    }

    /**
     * @return HasMany<DiscordMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(DiscordMember::class, 'discord_guild_id');
    }

    protected static function newFactory(): DiscordGuildFactory
    {
        return DiscordGuildFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'synced_at' => 'datetime',
            'premium_tier' => 'integer',
            'member_count' => 'integer',
        ];
    }
}
