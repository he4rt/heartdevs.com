<?php

declare(strict_types=1);

namespace He4rt\Moderation\Appeals;

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationAppealFactory;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $action_id
 * @property string $appellant_id
 * @property string $reason_category
 * @property string|null $reason_text
 * @property AppealStatus $status
 * @property string|null $reviewer_id
 * @property string|null $reviewer_notes
 * @property CarbonInterface|null $resolved_at
 * @property CarbonInterface $sla_deadline
 * @property string|null $tenant_id
 * @property CarbonInterface $created_at
 */
#[Table(name: 'moderation_appeals', timestamps: false)]
#[UseFactory(factoryClass: ModerationAppealFactory::class)]
final class ModerationAppeal extends Model
{
    /** @use HasFactory<ModerationAppealFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<ModerationAction, $this> */
    public function action(): BelongsTo
    {
        return $this->belongsTo(ModerationAction::class, 'action_id');
    }

    /** @return BelongsTo<User, $this> */
    public function appellant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appellant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    protected static function newFactory(): ModerationAppealFactory
    {
        return ModerationAppealFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'resolved_at' => 'datetime',
            'sla_deadline' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
