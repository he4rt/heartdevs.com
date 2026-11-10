<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Reaction\Actions\ReactWith;
use He4rt\Activity\Reaction\DTOs\ReactWithDTO;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Queries\ReactionSummary;
use He4rt\Identity\User\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Reactions extends Component
{
    #[Locked]
    public string $timelineId;

    /** @var array<string, int> */
    public array $counts = [];

    public ?string $mine = null;

    /** @param array<string, int> $counts */
    public function mount(string $timelineId, array $counts = [], ?string $mine = null): void
    {
        $this->timelineId = $timelineId;
        $this->counts = $counts;
        $this->mine = $mine;
    }

    public function reactWith(string $reaction, #[CurrentUser] ?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $case = TimelineReaction::tryFrom($reaction);
        if ($case === null) {
            return;
        }

        resolve(ReactWith::class)->handle(new ReactWithDTO($user->id, $this->timelineId, $case));

        $summary = resolve(ReactionSummary::class)
            ->forTimelines([$this->timelineId], $user->id)
            ->first();

        $this->counts = $summary->counts;
        $this->mine = $summary->mine?->value;

        $this->dispatch('timeline.reaction-updated');
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.reactions', [
            'reactions' => TimelineReaction::cases(),
        ]);
    }
}
