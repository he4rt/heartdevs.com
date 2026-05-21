<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\Carbon;
use He4rt\IntegrationDiscord\Database\Factories\DiscordChannelFactory;
use He4rt\IntegrationDiscord\Enums\DiscordChannelType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $discord_guild_id
 * @property string $discord_channel_id
 * @property int|null $parent_id
 * @property string $name
 * @property DiscordChannelType $type
 * @property string|null $topic
 * @property int $position
 * @property bool $nsfw
 * @property int|null $bitrate
 * @property int|null $user_limit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscordGuild $guild
 * @property-read DiscordChannel|null $parent
 * @property-read Collection<int, DiscordChannel> $children
 */
#[Fillable([
    'discord_guild_id',
    'discord_channel_id',
    'parent_id',
    'name',
    'type',
    'topic',
    'position',
    'nsfw',
    'bitrate',
    'user_limit',
])]
final class DiscordChannel extends Model
{
    /** @use HasFactory<DiscordChannelFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DiscordGuild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(DiscordGuild::class, 'discord_guild_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    protected static function newFactory(): DiscordChannelFactory
    {
        return DiscordChannelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscordChannelType::class,
            'nsfw' => 'boolean',
            'position' => 'integer',
            'bitrate' => 'integer',
            'user_limit' => 'integer',
        ];
    }
}
