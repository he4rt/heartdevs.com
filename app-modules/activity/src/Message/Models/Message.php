<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\Carbon;
use He4rt\Activity\Database\Factories\MessageFactory;
use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Reaction\Concerns\HasReactions;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $external_identity_id
 * @property string|null $provider_message_id
 * @property string|null $channel_id
 * @property string $content
 * @property int $obtained_experience
 * @property Carbon|null $sent_at
 * @property int $tenant_id
 * @property array<string, mixed>|null $metadata
 * @property int $reactions_count
 * @property int $reactions_total
 * @property MessageKind|null $kind
 * @property int|null $raw_message_type
 * @property MessageSourceKind|null $source_kind
 * @property bool $is_pinned
 * @property bool $mentions_everyone
 * @property int $mention_role_count
 * @property Carbon|null $edited_at
 * @property string|null $reply_to_provider_message_id
 * @property string|null $reply_to_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'id',
    'tenant_id',
    'external_identity_id',
    'provider_message_id',
    'channel_id',
    'content',
    'sent_at',
    'obtained_experience',
    'metadata',
    'reactions_count',
    'reactions_total',
    'kind',
    'raw_message_type',
    'source_kind',
    'is_pinned',
    'mentions_everyone',
    'mention_role_count',
    'edited_at',
    'reply_to_provider_message_id',
    'reply_to_message_id',
])]
#[Table(name: 'messages')]
final class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;
    use HasReactions;
    use HasUuids;

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }

    /**
     * @return HasMany<MessageMention, $this>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class, 'message_id');
    }

    /**
     * @return HasOne<MessageThread, $this>
     */
    public function thread(): HasOne
    {
        return $this->hasOne(MessageThread::class, 'message_id');
    }

    /**
     * @return HasMany<MessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }

    /**
     * @return HasMany<MessageEmbed, $this>
     */
    public function embeds(): HasMany
    {
        return $this->hasMany(MessageEmbed::class, 'message_id');
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'edited_at' => 'datetime',
            'kind' => MessageKind::class,
            'source_kind' => MessageSourceKind::class,
            'is_pinned' => 'boolean',
            'mentions_everyone' => 'boolean',
        ];
    }
}
