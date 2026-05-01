<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Models;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// `state` stays a plain string because two separate flows write it with
// disjoint vocabularies: gamification writes `muted`/`unmuted`/`disabled`
// (VoiceStatesEnum) and the Discord ETL writes `joined`/`left`
// (VoiceEventKind). Casting to either enum would break the other.
final class Voice extends Model
{
    protected $table = 'voice_messages';

    protected $fillable = [
        'tenant_id',
        'external_identity_id',
        'provider_message_id',
        'channel_name',
        'state',
        'occurred_at',
        'obtained_experience',
    ];

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
