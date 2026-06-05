<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Portal\Retrospective\CommunityRetrospective;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('portal::components.layouts.app')]
#[Title('Quem fez a He4rt bater')]
final class CommunityRetrospectivePage extends Component
{
    #[Url]
    public ?string $since = null;

    #[Url]
    public ?string $until = null;

    public function render(): View
    {
        $since = $this->since !== null && $this->since !== ''
            ? CarbonImmutable::parse($this->since)->startOfDay()
            : CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek();

        $until = $this->until !== null && $this->until !== ''
            ? CarbonImmutable::parse($this->until)->endOfDay()
            : CarbonImmutable::now();

        return view('portal::community-retrospective', [
            'data' => new CommunityRetrospective($since, $until)->build(),
            'sinceValue' => $since->toDateString(),
            'untilValue' => $until->toDateString(),
        ]);
    }
}
