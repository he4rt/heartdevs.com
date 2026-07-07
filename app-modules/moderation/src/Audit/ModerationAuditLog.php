<?php

declare(strict_types=1);

namespace He4rt\Moderation\Audit;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_type
 * @property string|null $actor_id
 * @property string|null $actor_type
 * @property string|null $case_id
 * @property array<string, mixed> $details
 * @property string|null $platform
 * @property CarbonInterface $created_at
 */
#[Table('moderation_audit_log', timestamps: false)]
final class ModerationAuditLog extends Model
{
    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
