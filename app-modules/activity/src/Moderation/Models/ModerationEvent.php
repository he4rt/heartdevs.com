<?php

declare(strict_types=1);

namespace He4rt\Activity\Moderation\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Timeline\Observers\ModerationEventObserver;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string|null $external_identity_id
 * @property string|null $moderator_identity_id
 * @property ModerationType $type
 * @property string|null $reason
 * @property string|null $source_identity_id
 * @property string|null $source_message_id
 * @property string|null $provider_message_id
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $occurred_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[ObservedBy(classes: ModerationEventObserver::class)]
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
