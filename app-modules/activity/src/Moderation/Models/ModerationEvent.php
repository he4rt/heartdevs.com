<?php

declare(strict_types=1);

namespace He4rt\Activity\Moderation\Models;

use Carbon\Carbon;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Timeline\Observers\ModerationEventObserver;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string|null $external_identity_id
 * @property string|null $moderator_identity_id
 * @property ModerationType $type
 * @property string|null $reason
 * @property string|null $source_identity_id
 * @property string|null $source_message_id
 * @property string|null $provider_message_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(ModerationEventObserver::class)]
#[Fillable([
    'id',
    'tenant_id',
    'external_identity_id',
    'moderator_identity_id',
    'type',
    'reason',
    'source_identity_id',
    'source_message_id',
    'provider_message_id',
    'metadata',
    'occurred_at',
])]
#[Table(name: 'moderation_events')]
final class ModerationEvent extends Model
{
    use HasUuids;

    /** @return BelongsTo<ExternalIdentity, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }

    /** @return BelongsTo<ExternalIdentity, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'moderator_identity_id');
    }

    /** @return BelongsTo<ExternalIdentity, $this> */
    public function sourceBot(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'source_identity_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'source_message_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => ModerationType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
