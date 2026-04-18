<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\Carbon;
use He4rt\Activity\Database\Factories\MessageFactory;
use He4rt\Activity\Reaction\Concerns\HasReactions;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }
}
