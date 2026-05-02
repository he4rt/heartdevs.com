<?php

declare(strict_types=1);

namespace He4rt\Moderation\Rules;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationRule extends Model
{
    use HasUuids;

    protected $table = 'moderation_rules';

    protected $fillable = [
        'name',
        'type',
        'platform',
        'pattern',
        'violation_type',
        'severity',
        'action_on_match',
        'is_active',
        'tenant_id',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'violation_type' => ViolationType::class,
            'severity' => Severity::class,
            'action_on_match' => ActionType::class,
            'is_active' => 'boolean',
        ];
    }
}
