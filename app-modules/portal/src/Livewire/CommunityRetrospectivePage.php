<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Actions\ComposeDeck;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\Models\Retrospective;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Página pública da retrospectiva. O visitante só assiste: monta a edição
 * publicada mais recente a partir do snapshot congelado, sem filtros e sem tocar
 * as fontes (ADR-0002). Em modo preview (rota autenticada), monta uma edição
 * específica — coletando ao vivo se ainda for rascunho — pelo mesmo render path,
 * então "ver rascunho" bate com o que será publicado.
 */
#[Layout(name: 'portal::components.layouts.deck')]
#[Title(content: 'Quem fez a He4rt bater')]
final class CommunityRetrospectivePage extends Component
{
    public ?string $retrospectiveId = null;

    public bool $preview = false;

    public function mount(?Retrospective $retrospective = null): void
    {
        if ($retrospective instanceof Retrospective) {
            // Preview de uma edição específica é só para operadores autenticados:
            // rascunhos não podem vazar pela URL pública.
            abort_unless(auth()->check(), 403);

            $this->retrospectiveId = $retrospective->id;
            $this->preview = true;
        }
    }

    public function render(): View
    {
        $retrospective = $this->resolveEdition();

        if (!$retrospective instanceof Retrospective) {
            return view('portal::community-retrospective', [
                'sources' => [],
                'since' => CarbonImmutable::now(),
                'until' => CarbonImmutable::now(),
                'coverTitle' => null,
                'coverIntro' => null,
                'closingText' => null,
                'stateKey' => 'empty',
            ]);
        }

        $snapshot = $this->preview && !$retrospective->isPublished()
            ? resolve(CompileSnapshot::class)->execute($retrospective->period(), $retrospective->filters())
            : ($retrospective->snapshot ?? new RetrospectiveSnapshot());

        $sources = resolve(ComposeDeck::class)->execute($snapshot, $retrospective->deck_config);

        return view('portal::community-retrospective', [
            'sources' => $sources,
            'since' => $retrospective->since,
            'until' => $retrospective->until,
            'coverTitle' => $retrospective->cover_title,
            'coverIntro' => $retrospective->cover_intro,
            'closingText' => $retrospective->closing_text,
            // Sem filtros do visitante: o deck só muda quando a edição muda.
            'stateKey' => $retrospective->id,
        ]);
    }

    private function resolveEdition(): ?Retrospective
    {
        if ($this->retrospectiveId !== null) {
            return Retrospective::query()->find($this->retrospectiveId);
        }

        return Retrospective::query()->published()->latest('published_at')->first();
    }
}
