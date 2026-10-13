<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Database\Factories\StreamSessionFactory;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $streamer_id
 * @property string $external_identity_id
 * @property string $platform_stream_id
 * @property string|null $title
 * @property string|null $category
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $ended_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Streamer $streamer
 * @property-read ExternalIdentity $identity
 * @property-read Collection<int, StreamEvent> $events
 */
#[Table(name: 'stream_sessions')]
#[UseFactory(factoryClass: StreamSessionFactory::class)]
final class StreamSession extends Model
{
    /** @use HasFactory<StreamSessionFactory> */
    use HasFactory;
    use HasUuids;

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

    /** @return HasMany<StreamEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(StreamEvent::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
