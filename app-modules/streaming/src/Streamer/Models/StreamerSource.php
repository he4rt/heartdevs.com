<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Database\Factories\StreamerSourceFactory;
use He4rt\Streaming\Enums\ChatReader;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $streamer_id
 * @property string $external_identity_id
 * @property bool $enabled
 * @property ChatReader|null $chat_reader
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Streamer $streamer
 * @property-read ExternalIdentity $identity
 */
#[Table(name: 'streamer_sources')]
#[UseFactory(factoryClass: StreamerSourceFactory::class)]
final class StreamerSource extends Model
{
    /** @use HasFactory<StreamerSourceFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'enabled' => true,
    ];

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

    public function showsChat(): bool
    {
        return $this->enabled && $this->chat_reader instanceof ChatReader;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'chat_reader' => ChatReader::class,
        ];
    }
}
