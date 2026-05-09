<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Facades\Filament;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use Illuminate\View\View;
use Livewire\Component;

final class Composer extends Component
{
    public string $content = '';

    public function post(): void
    {
        if (mb_trim($this->content) === '') {
            return;
        }

        $tenant = Filament::getTenant();

        resolve(CreatePost::class)->handle(new CreatePostDTO(
            userId: auth()->id(),
            tenantId: $tenant->id,
            content: $this->content,
        ));

        $this->reset('content');
        $this->dispatch('timeline.post-created');
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.composer');
    }
}
