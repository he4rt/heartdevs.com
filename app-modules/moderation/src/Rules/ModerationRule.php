<?php

declare(strict_types=1);

namespace He4rt\Moderation\Rules;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property Platform|null $platform
 * @property string $pattern
 * @property ViolationType $violation_type
 * @property Severity $severity
 * @property ActionType $action_on_match
 * @property bool $is_active
 * @property int|null $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('moderation_rules')]
final class ModerationRule extends Model
{
    use HasUuids;

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
