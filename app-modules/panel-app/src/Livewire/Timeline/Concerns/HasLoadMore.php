<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline\Concerns;

use Livewire\Attributes\Locked;

trait HasLoadMore
{
    #[Locked]
    public int $perPage = 15;

    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + 15, 100);
    }
}
