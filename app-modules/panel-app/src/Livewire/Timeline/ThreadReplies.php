<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Timeline\Actions\DeleteReply;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class ThreadReplies extends Component
{
    #[Locked]
    public string $timelineId;

    #[On('timeline.reply-created')]
    public function refresh(): void {}

    public function deleteReply(string $replyId, #[CurrentUser] ?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $reply = Timeline::query()->findOrFail($replyId);

        resolve(DeleteReply::class)->handle($user, $reply);

        $this->dispatch('timeline.reply-created');
    }

    public function render(): View
    {
        $replies = Timeline::query()
            ->where('root_id', $this->timelineId)
            ->whereNotNull('parent_id')
            ->with(['user', 'postable'])
            ->oldest()
            ->get();

        return view('panel-app::livewire.timeline.thread-replies', [
            'replies' => $replies,
        ]);
    }
}
