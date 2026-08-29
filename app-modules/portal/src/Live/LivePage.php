<?php

declare(strict_types=1);

namespace He4rt\Portal\Live;

use He4rt\Live\Actions\CheckLiveStatusAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
final class LivePage extends Component
{
    public function render(CheckLiveStatusAction $status): View
    {
        return view('portal::live', ['status' => $status->execute()]);
    }
}
