<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property Collection<int, ModerationCase> $cases
 * @property ModerationCase|null $selectedCase
 */
class ModerationQueue extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

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

    public function handleAction(): Action
    {

        return Action::make('handle')
            ->label(__('panel-admin::moderation.queue.actions.take_action'))
            ->icon(Heroicon::OutlinedArrowUpCircle)
            ->color(Color::Red)
            ->size('lg')
            ->requiresConfirmation()
            ->schema([
                Select::make('action_type')
                    ->options(ActionType::class)
                    ->required(),
                Select::make('duration')
                    ->options([
                        '24h' => '24 hours',
                        '7d' => '7 days',
                        '30d' => '30 days',
                        'permanent' => 'Permanent',
                    ]),
                CheckboxList::make('target_platforms')
                    ->options(Platform::class)
                    ->required(),
                Textarea::make('reason')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $case = $this->selectedCase;

                if (!$case) {
                    return;
                }

                $action = ModerationAction::query()->create([
                    'case_id' => $case->id,
                    'moderator_id' => auth()->id(),
                    'action_type' => $data['action_type'],
                    'target_platforms' => $data['target_platforms'],
                    'duration' => $data['duration'],
                    'reason' => $data['reason'],
                    'automated' => false,
                    'tenant_id' => $case->tenant_id,
                ]);

                if ($case->author) {
                    dispatch_sync(new ExecuteAction($action, $case->author));
                }

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.success'))
                    ->send();

                $this->advanceToNextCase();
            });
    }

    public function escalateAction(): Action
    {
        return Action::make('escalate')
            ->label(__('panel-admin::moderation.queue.actions.escalate'))
            ->icon(Heroicon::OutlinedArrowUpCircle)
            ->color('warning')
            ->size('lg')
            ->requiresConfirmation()
            ->schema([
                Select::make('action_type')
                    ->options(ActionType::class)
                    ->required(),
                Select::make('duration')
                    ->options([
                        '24h' => '24 hours',
                        '7d' => '7 days',
                        '30d' => '30 days',
                        'permanent' => 'Permanent',
                    ]),
                CheckboxList::make('target_platforms')
                    ->options(Platform::class)
                    ->required(),
                Textarea::make('reason')
                    ->required(),
            ])
            ->action(function (): void {
                $this->selectedCase?->update([
                    'status' => CaseStatus::Escalated,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.escalated'))
                    ->send();

                $this->advanceToNextCase();
            });
    }

    public function dismissAction(): Action
    {
        return Action::make('dismiss')
            ->label(__('panel-admin::moderation.queue.actions.dismiss'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->size('lg')
            ->requiresConfirmation()
            ->action(function (): void {
                $this->selectedCase?->update([
                    'status' => CaseStatus::Dismissed,
                    'resolved_at' => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.dismissed'))
                    ->send();

                $this->advanceToNextCase();
            });
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

    private function advanceToNextCase(): void
    {
        unset($this->cases, $this->selectedCase);

        $cases = $this->cases;
        $currentIndex = $cases->search(fn (ModerationCase $c) => $c->id === $this->selectedCaseId);

        if ($currentIndex !== false && $cases->has($currentIndex + 1)) {
            $this->selectedCaseId = $cases->get($currentIndex + 1)->id;
        } else {
            $this->selectedCaseId = $cases->first()?->id;
        }
    }
}
