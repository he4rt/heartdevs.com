<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Facades\Filament;
use He4rt\Activity\Timeline\Actions\TogglePinPost;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class PostShow extends Component
{
    #[Locked]
    public string $timelineId;

    public bool $showReplies = true;

    #[On('timeline.post-updated')]
    public function refresh(): void {}

    public function togglePin(#[CurrentUser] ?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $timeline = Timeline::query()
            ->where('id', $this->timelineId)
            ->where('tenant_id', Filament::getTenant()->id)
            ->firstOrFail();

        resolve(TogglePinPost::class)->handle($user, $timeline);

        $this->dispatch('timeline.post-updated');
    }

    public function render(): View
    {
        $timeline = Timeline::query()
            ->where('id', $this->timelineId)
            ->where('tenant_id', Filament::getTenant()->id)
            ->with([
                'user',
                'postable',
                'reactions',
                'children' => fn ($q) => $q->with('user', 'postable')->latest()->limit(3),
            ])
            ->withCount('children', 'reactions')
            ->firstOrFail();

        return view('panel-app::livewire.timeline.post-show', [
            'timeline' => $timeline,
        ]);
    }
}
