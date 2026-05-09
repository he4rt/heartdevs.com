<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Timeline\Queries\TimelineFeed;
use He4rt\PanelApp\Livewire\Timeline\Concerns\HasLoadMore;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class Feed extends Component
{
    use HasLoadMore;

    #[Locked]
    public int $tenantId;

    #[On('timeline.post-created')]
    #[On('timeline.reply-created')]
    public function refresh(): void {}

    public function render(): View
    {
        $items = new TimelineFeed($this->tenantId)
            ->builder()
            ->with([
                'user',
                'postable',
                'children' => fn ($q) => $q->with('user', 'postable')->latest()->limit(3),
            ])
            ->withCount('children', 'reactions')
            ->simplePaginate($this->perPage);

        return view('panel-app::livewire.timeline.feed', [
            'items' => $items,
        ]);
    }
}
