<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_type
 * @property string|null $broadcaster_user_id
 * @property string|null $user_id
 * @property string|null $twitch_message_id
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TwitchEventLog extends Model
{
    protected $fillable = [
        'event_type',
        'broadcaster_user_id',
        'user_id',
        'twitch_message_id',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
