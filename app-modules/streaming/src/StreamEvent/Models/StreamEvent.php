<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Database\Factories\StreamEventFactory;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Casts\AsStreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $streamer_id
 * @property string $external_identity_id
 * @property string|null $stream_session_id
 * @property StreamEventType $type
 * @property string|null $actor_platform_id
 * @property string|null $actor_login
 * @property string|null $actor_display_name
 * @property StreamEventDetails|null $details
 * @property string $source_event_id
 * @property CarbonInterface $occurred_at
 * @property CarbonInterface|null $created_at
 * @property-read Streamer $streamer
 * @property-read ExternalIdentity $identity
 * @property-read StreamSession|null $session
 */
#[Table(name: 'stream_events')]
#[UseFactory(factoryClass: StreamEventFactory::class)]
final class StreamEvent extends Model
{
    /** @use HasFactory<StreamEventFactory> */
    use HasFactory;
    use HasUuids;

    public const null UPDATED_AT = null;

    /** @return BelongsTo<Streamer, $this> */
    public function streamer(): BelongsTo
    {
        return $this->belongsTo(Streamer::class);
    }

    /** @return BelongsTo<ExternalIdentity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }

    /** @return BelongsTo<StreamSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(StreamSession::class, 'stream_session_id');
    }

    public function actor(): ?StreamActor
    {
        $hasActor = $this->actor_platform_id !== null && $this->actor_login !== null;

        if (!$hasActor) {
            return null;
        }

        return new StreamActor(
            platformId: $this->actor_platform_id,
            login: $this->actor_login,
            displayName: $this->actor_display_name ?? $this->actor_login,
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => StreamEventType::class,
            'details' => AsStreamEventDetails::class,
            'occurred_at' => 'datetime',
        ];
    }
}
