<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Raw, append-only lake of GitHub webhook deliveries (deduped by delivery_id),
 * for audit and replay. Mirrors discord_event_logs.
 *
 * @property int $id
 * @property string $event_type
 * @property string|null $repo
 * @property string|null $actor_login
 * @property string|null $delivery_id
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class GithubEventLog extends Model
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
