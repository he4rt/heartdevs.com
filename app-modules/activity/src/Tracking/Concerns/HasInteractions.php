<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Concerns;

use He4rt\Activity\Tracking\Models\Interaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasInteractions
{
    /**
     * @return HasMany<Interaction, $this>
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }
}
