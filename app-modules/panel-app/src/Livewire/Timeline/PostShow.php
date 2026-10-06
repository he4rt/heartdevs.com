<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Reaction\DTOs\TimelineReactionSummary;
use He4rt\Activity\Reaction\Queries\ReactionSummary;
use He4rt\Activity\Timeline\Actions\TogglePinPost;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class PostShow extends Component
{
    #[Locked]
    public string $timelineId;

    public bool $showReplies = true;

    /** @var array<string, int> */
    public array $reactionCounts = [];

    public ?string $reactionMine = null;

    public bool $reactionsProvided = false;

    /** @param array<string, int> $reactionCounts */
    public function mount(
        string $timelineId,
        bool $showReplies = true,
        array $reactionCounts = [],
        ?string $reactionMine = null,
        bool $reactionsProvided = false,
    ): void {
        $this->timelineId = $timelineId;
        $this->showReplies = $showReplies;
        $this->reactionCounts = $reactionCounts;
        $this->reactionMine = $reactionMine;
        $this->reactionsProvided = $reactionsProvided;
    }

    #[On(event: 'timeline.post-updated')]
    #[On(event: 'timeline.reaction-updated')]
    public function refresh(): void {}

    public function togglePin(#[CurrentUser] ?User $user): void
    {
        if (!config('he4rt.features.timeline_pin') || !$user instanceof User) {
            return;
        }

        $timeline = Timeline::query()
            ->where('id', $this->timelineId)->firstOrFail();

        resolve(TogglePinPost::class)->handle($user, $timeline);

        $this->dispatch('timeline.post-updated');
    }

    public function render(): View
    {
        $timeline = Timeline::query()
            ->where('id', $this->timelineId)->with([
                'user',
                'postable',
                'children' => fn (Relation $q) => $q->with('user', 'postable')->latest(),
            ])
            ->withCount('children')
            ->firstOrFail();

        $visibleReplies = $this->showReplies
            ? $timeline->children->take(3)
            : collect();

        /** @var list<string> $idsToFetch */
        $idsToFetch = $visibleReplies->pluck('id')->all();

        if (!$this->reactionsProvided) {
            array_unshift($idsToFetch, $this->timelineId);
        }

        /** @var Collection<string, TimelineReactionSummary> $reactionSummaries */
        $reactionSummaries = $idsToFetch === []
            ? Collection::make()
            : resolve(ReactionSummary::class)->forTimelines($idsToFetch, auth()->id());

        if ($this->reactionsProvided) {
            $rootReactionCounts = $this->reactionCounts;
            $rootReactionMine = $this->reactionMine;
        } else {
            $rootSummary = $reactionSummaries->get($this->timelineId);
            $rootReactionCounts = $rootSummary?->counts ?? [];
            $rootReactionMine = $rootSummary?->mine?->value;
        }

        return view('panel-app::livewire.timeline.post-show', [
            'timeline' => $timeline,
            'rootReactionCounts' => $rootReactionCounts,
            'rootReactionMine' => $rootReactionMine,
            'reactionSummaries' => $reactionSummaries,
        ]);
    }
}
