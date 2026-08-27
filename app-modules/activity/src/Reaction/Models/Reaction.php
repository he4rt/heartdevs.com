<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $reactable_type
 * @property string $reactable_id
 * @property string $emoji_key
 * @property string|null $emoji_id
 * @property string|null $emoji_name
 * @property int $count
 * @property int $count_burst
 * @property int $count_normal
 */
#[Table(name: 'activity_reactions')]
final class Reaction extends Model
{
    use HasUuids;

    /** @return MorphTo<Model, $this> */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCustomEmoji(): bool
    {
        return $this->emoji_id !== null;
    }
}
