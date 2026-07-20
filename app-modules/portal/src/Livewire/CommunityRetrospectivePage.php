<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Portal\Retrospective\RetrospectiveDeck;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.deck')]
#[Title(content: 'Quem fez a He4rt bater')]
final class CommunityRetrospectivePage extends Component
{
    #[Url]
    public ?string $since = null;

    #[Url]
    public ?string $until = null;

    #[Url]
    public bool $hideBots = true;

    public function setPreset(string $preset): void
    {
        $this->since = match ($preset) {
            'mes' => CarbonImmutable::now()->subMonth()->toDateString(),
            // "Tudo" = desde antes da fundação da comunidade; cobre todo o histórico
            // sem acoplar o portal à menor data de nenhuma fonte específica.
            'tudo' => CarbonImmutable::create(2_015, 1, 1)->toDateString(),
            default => CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek()->toDateString(),
        };

        $this->until = CarbonImmutable::now()->toDateString();
    }

    public function render(): View
    {
        $since = $this->since !== null && $this->since !== ''
            ? CarbonImmutable::parse($this->since)->startOfDay()
            : CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek();

        $until = $this->until !== null && $this->until !== ''
            ? CarbonImmutable::parse($this->until)->endOfDay()
            : CarbonImmutable::now();

        $sources = resolve(RetrospectiveDeck::class)->compose(
            Period::of($since, $until),
            new SourceFilters(hideBots: $this->hideBots),
        );

        return view('portal::community-retrospective', [
            'sources' => $sources,
            'since' => $since,
            'until' => $until,
            'hideBots' => $this->hideBots,
            'stateKey' => md5((string) json_encode([$this->since, $this->until, $this->hideBots])),
        ]);
    }
}
