<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Reaction\DTOs\TimelineReactionSummary;
use He4rt\Activity\Reaction\Queries\ReactionSummary;
use He4rt\Activity\Timeline\Queries\TimelineFeed;
use He4rt\PanelApp\Livewire\Timeline\Concerns\HasLoadMore;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class Feed extends Component
{
    use HasLoadMore;

    #[On(event: 'timeline.post-created')]
    #[On(event: 'timeline.reply-created')]
    #[On(event: 'timeline.reply-deleted')]
    #[On(event: 'timeline.reaction-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        $items = new TimelineFeed()
            ->builder()
            ->with(['user', 'postable'])
            ->withCount('children')
            ->simplePaginate($this->perPage);

        /** @var Collection<string, TimelineReactionSummary> $reactionSummaries */
        $reactionSummaries = resolve(ReactionSummary::class)
            ->forTimelines($items->getCollection()->pluck('id'), auth()->id());

        return view('panel-app::livewire.timeline.feed', [
            'items' => $items,
            'reactionSummaries' => $reactionSummaries,
        ]);
    }
}
