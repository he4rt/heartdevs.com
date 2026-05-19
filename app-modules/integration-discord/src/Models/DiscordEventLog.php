<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Illuminate\Database\Eloquent\Model;

final class DiscordEventLog extends Model
{
    protected $fillable = [
        'event_type',
        'guild_id',
        'user_id',
        'channel_id',
        'payload',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
