<?php

declare(strict_types=1);

namespace He4rt\Message\Models;

use He4rt\Message\Database\Factories\MessageFactory;
use He4rt\Provider\Models\Provider;
use He4rt\Season\Models\Season;
use He4rt\Tenant\Models\Tenant;
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
        'season_id',
        'channel_id',
        'content',
        'sent_at',
        'obtained_experience',
    ];

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }
}
