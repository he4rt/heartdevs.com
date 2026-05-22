<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Models;

use Carbon\Carbon;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $external_identity_id
 * @property int $tenant_id
 * @property string|null $provider_message_id
 * @property string $channel_name
 * @property string $state
 * @property int $obtained_experience
 * @property Carbon|null $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// `state` stays a plain string because two separate flows write it with
// disjoint vocabularies: gamification writes `muted`/`unmuted`/`disabled`
// (VoiceStatesEnum) and the Discord ETL writes `joined`/`left`
// (VoiceEventKind). Casting to either enum would break the other.
#[Table(name: 'voice_messages')]
final class Voice extends Model
{
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
