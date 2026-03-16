<?php

declare(strict_types=1);

namespace He4rt\Message\Models;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Message\Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Message extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'messages';

    protected $fillable = [
        'id',
        'tenant_id',
        'provider_id',
        'provider_message_id',
        'channel_id',
        'content',
        'sent_at',
        'obtained_experience',
    ];

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'provider_id');
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
}
