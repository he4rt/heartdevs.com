<?php

declare(strict_types=1);

namespace He4rt\Moderation\Audit;

use Illuminate\Database\Eloquent\Model;

final class ModerationAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'moderation_audit_log';

    protected $fillable = [
        'event_type',
        'actor_id',
        'actor_type',
        'case_id',
        'details',
        'platform',
        'tenant_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
