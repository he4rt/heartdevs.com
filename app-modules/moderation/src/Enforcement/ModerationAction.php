<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enforcement;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Database\Factories\ModerationActionFactory;
use He4rt\Moderation\Enums\ActionType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $case_id
 * @property string|null $moderator_id
 * @property ActionType $action_type
 * @property array<int, string> $target_platforms
 * @property string|null $duration
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $execution_results
 * @property bool $automated
 * @property int|null $tenant_id
 * @property Carbon $created_at
 */
#[Table('moderation_actions', timestamps: false)]
#[UseFactory(ModerationActionFactory::class)]
final class ModerationAction extends Model
{
    /** @use HasFactory<ModerationActionFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<ModerationCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasOne<ModerationAppeal, $this> */
    public function appeal(): HasOne
    {
        return $this->hasOne(ModerationAppeal::class, 'action_id');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'target_platforms' => 'array',
            'metadata' => 'array',
            'execution_results' => 'array',
            'automated' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
