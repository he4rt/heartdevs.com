<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $tenant_id
 * @property string $event_type
 * @property string|null $broadcaster_user_id
 * @property string|null $user_id
 * @property string|null $twitch_message_id
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class TwitchEventLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'event_type',
        'broadcaster_user_id',
        'user_id',
        'twitch_message_id',
        'payload',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

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
