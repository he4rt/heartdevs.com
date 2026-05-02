<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Livewire;

use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property Collection<int, ModerationCase> $cases
 * @property ModerationCase|null $selectedCase
 */
class ModerationQueue extends Component
{
    public string $statusFilter = 'pending';

    public string $platformFilter = 'all';

    public string $violationFilter = 'all';

    public string $severityFilter = 'all';

    public ?string $selectedCaseId = null;

    public function mount(): void
    {
        $this->selectedCaseId = $this->cases->first()?->id;
    }

    /** @return Collection<int, ModerationCase> */
    #[Computed]
    public function cases(): Collection
    {
        return ModerationCase::query()
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->platformFilter !== 'all', fn ($q) => $q->where('source_platform', $this->platformFilter))
            ->when($this->violationFilter !== 'all', fn ($q) => $q->where('violation_type', $this->violationFilter))
            ->when($this->severityFilter !== 'all', fn ($q) => $q->where('severity', $this->severityFilter))
            ->with(['author', 'reports'])
            ->orderByDesc('priority')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function selectedCase(): ?ModerationCase
    {
        if (!$this->selectedCaseId) {
            return null;
        }

        return ModerationCase::query()
            ->with(['author', 'reports.reporter', 'actions.moderator'])
            ->find($this->selectedCaseId);
    }

    public function selectCase(string $id): void
    {
        $this->selectedCaseId = $id;
        unset($this->selectedCase);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetSelection();
    }

    public function updatedPlatformFilter(): void
    {
        $this->resetSelection();
    }

    public function updatedViolationFilter(): void
    {
        $this->resetSelection();
    }

    public function updatedSeverityFilter(): void
    {
        $this->resetSelection();
    }

    public function render(): View
    {
        return view('panel-admin::moderation.queue.index');
    }

    private function resetSelection(): void
    {
        unset($this->cases, $this->selectedCase);
        $this->selectedCaseId = $this->cases->first()?->id;
    }
}
