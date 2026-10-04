<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Database\Factories\StreamerFactory;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Casts\AsStreamerSettings;
use He4rt\Streaming\Streamer\Data\StreamerSettings;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $user_id
 * @property StreamerStatus $status
 * @property string $overlay_token
 * @property string $overlay_token_hash
 * @property StreamerSettings $settings
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read User $user
 * @property-read Collection<int, StreamerSource> $sources
 */
#[Table(name: 'streamers')]
#[UseFactory(factoryClass: StreamerFactory::class)]
final class Streamer extends Model
{
    /** @use HasFactory<StreamerFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    private const int CHANNEL_FINGERPRINT_LENGTH = 12;

    /** @var list<string> */
    protected $hidden = ['overlay_token', 'overlay_token_hash'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<StreamerSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(StreamerSource::class);
    }

    public function isActive(): bool
    {
        return $this->status === StreamerStatus::Active;
    }

    public function overlayChannel(): string
    {
        return sprintf(
            'overlay.%s.%s',
            $this->getKey(),
            mb_substr($this->overlay_token_hash, 0, self::CHANNEL_FINGERPRINT_LENGTH),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StreamerStatus::class,
            'overlay_token' => 'encrypted',
            'settings' => AsStreamerSettings::class,
        ];
    }
}
