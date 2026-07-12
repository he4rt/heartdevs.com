<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
#[Title(content: 'Home')]
final class Homepage extends Component
{
    public function render(): View
    {
        return view('portal::homepage');
    }
}
