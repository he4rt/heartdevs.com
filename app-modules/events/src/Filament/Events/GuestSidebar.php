<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Livewire\Concerns\HasTenantMenu;
use Filament\Livewire\Concerns\HasUserMenu;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class GuestSidebar extends Component implements HasActions, HasSchemas
{
    use HasTenantMenu;
    use HasUserMenu;
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[On('refresh-sidebar')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('events::filament.sidebar');
    }
}
