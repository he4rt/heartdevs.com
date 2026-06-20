<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_type
 * @property string|null $guild_id
 * @property string|null $user_id
 * @property string|null $channel_id
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class DiscordEventLog extends Model
{
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
