<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Concerns;

use He4rt\Activity\Reaction\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReactions
{
    /** @return MorphMany<Reaction, $this> */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }
}
