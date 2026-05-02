<?php

declare(strict_types=1);

namespace He4rt\Moderation\Cases\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationCaseFactory;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $content_type
 * @property string $content_id
 * @property array<string, mixed>|null $content_snapshot
 * @property Platform $source_platform
 * @property CaseSource $source
 * @property CaseStatus $status
 * @property int $priority
 * @property Severity|null $severity
 * @property ViolationType|null $violation_type
 * @property array<string, float>|null $ai_scores
 * @property string|null $classifier_version
 * @property ActionType|null $suggested_action
 * @property string|null $assigned_to
 * @property string|null $author_id
 * @property string|null $tenant_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('moderation_cases')]
#[UseFactory(ModerationCaseFactory::class)]
final class ModerationCase extends Model
{
    /** @use HasFactory<ModerationCaseFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'content_type',
        'content_id',
        'content_snapshot',
        'source_platform',
        'source',
        'status',
        'priority',
        'severity',
        'violation_type',
        'ai_scores',
        'classifier_version',
        'suggested_action',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'author_id',
        'tenant_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<ModerationReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(ModerationReport::class, 'case_id');
    }

    /** @return HasMany<ModerationAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'case_id');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'source_platform' => Platform::class,
            'source' => CaseSource::class,
            'status' => CaseStatus::class,
            'severity' => Severity::class,
            'violation_type' => ViolationType::class,
            'suggested_action' => ActionType::class,
            'ai_scores' => 'array',
            'content_snapshot' => 'array',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
