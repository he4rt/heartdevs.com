<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationDiscord\Database\Factories\DiscordEventLogFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[UseFactory(factoryClass: DiscordEventLogFactory::class)]
final class DiscordEventLog extends Model
{
    /** @use HasFactory<DiscordEventLogFactory> */
    use HasFactory;

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
