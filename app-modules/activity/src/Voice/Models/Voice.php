<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Database\Factories\VoiceFactory;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $external_identity_id
 * @property string $tenant_id
 * @property string|null $provider_message_id
 * @property string $channel_name
 * @property string|null $channel_id
 * @property string $state
 * @property int $obtained_experience
 * @property CarbonInterface|null $occurred_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
// `state` is a plain string: the voice experience ticker writes
// VoiceStatesEnum values (muted/unmuted/disabled) and the historical
// ETL import writes joined/left as raw strings.
#[UseFactory(VoiceFactory::class)]
#[Table(name: 'voice_messages')]
final class Voice extends Model
{
    /** @use HasFactory<VoiceFactory> */
    use HasFactory;

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
