<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use He4rt\Activity\Timeline\Actions\DeleteReply;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\Concerns\HasLoadMore;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class ThreadReplies extends Component
{
    use HasLoadMore;

    #[Locked]
    public string $timelineId;

    #[On(event: 'timeline.reply-created')]
    #[On(event: 'timeline.reply-deleted')]
    public function refresh(): void {}

    public function deleteReply(string $replyId, #[CurrentUser] ?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $reply = Timeline::query()
            ->where('id', $replyId)->firstOrFail();

        resolve(DeleteReply::class)->handle($user, $reply);

        $this->dispatch('timeline.reply-deleted');
    }

    public function render(): View
    {
        $replies = Timeline::query()
            ->where('root_id', $this->timelineId)->whereNotNull('parent_id')
            ->with(['user.media', 'postable'])
            ->oldest()
            ->simplePaginate($this->perPage);

        return view('panel-app::livewire.timeline.thread-replies', [
            'replies' => $replies,
        ]);
    }
}
