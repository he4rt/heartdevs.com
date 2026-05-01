<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Models;

use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $reactable_type
 * @property string $reactable_id
 * @property string $emoji_key
 * @property string|null $emoji_id
 * @property string|null $emoji_name
 * @property int $count
 * @property int $count_burst
 * @property int $count_normal
 */
final class Reaction extends Model
{
    use HasUuids;

    protected $table = 'activity_reactions';

    protected $fillable = [
        'id',
        'tenant_id',
        'reactable_type',
        'reactable_id',
        'emoji_key',
        'emoji_id',
        'emoji_name',
        'count',
        'count_burst',
        'count_normal',
    ];

    /** @return MorphTo<Model, $this> */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isCustomEmoji(): bool
    {
        return $this->emoji_id !== null;
    }
}
