<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Livewire;

use DomainException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Appeals\ReviewAppeal;
use He4rt\Moderation\Enums\AppealStatus;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property Collection<int, ModerationAppeal> $appeals
 * @property ModerationAppeal|null $selectedAppeal
 */
class AppealQueue extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $statusFilter = 'pending';

    public ?string $selectedAppealId = null;

    public function mount(): void
    {
        $this->selectedAppealId = $this->appeals->first()?->id;
    }

    /** @return Collection<int, ModerationAppeal> */
    #[Computed]
    public function appeals(): Collection
    {
        return ModerationAppeal::query()
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->with(['appellant', 'action.moderator', 'action.case'])
            ->orderBy('sla_deadline')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function selectedAppeal(): ?ModerationAppeal
    {
        if (!$this->selectedAppealId) {
            return null;
        }

        return ModerationAppeal::query()
            ->with([
                'appellant',
                'reviewer',
                'action.moderator',
                'action.case' => fn ($q) => $q->with(['author', 'reports.reporter']),
            ])
            ->find($this->selectedAppealId);
    }

    public function selectAppeal(string $id): void
    {
        $this->selectedAppealId = $id;
        unset($this->selectedAppeal);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetSelection();
    }

    public function upholdAction(): Action
    {
        return Action::make('uphold')
            ->label(__('panel-admin::moderation.appeal_queue.actions.uphold'))
            ->icon(Heroicon::OutlinedHandThumbDown)
            ->color(Color::Rose)
            ->size('lg')
            ->visible(fn () => !($this->selectedAppeal?->status->isResolved() ?? true))
            ->schema([
                Textarea::make('reviewer_notes')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.reviewer_notes'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $appeal = $this->selectedAppeal;

                if (!$appeal) {
                    return;
                }

                try {
                    resolve(ReviewAppeal::class)->handle(
                        $appeal,
                        auth()->user(),
                        AppealStatus::Upheld,
                        $data['reviewer_notes'],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('panel-admin::moderation.appeal_queue.actions.upheld'))
                        ->send();

                    $this->advanceToNextAppeal();
                } catch (DomainException $domainException) {
                    Notification::make()
                        ->danger()
                        ->title($domainException->getMessage())
                        ->send();
                }
            });
    }

    public function overturnAction(): Action
    {
        return Action::make('overturn')
            ->label(__('panel-admin::moderation.appeal_queue.actions.overturn'))
            ->icon(Heroicon::OutlinedHandThumbUp)
            ->color(Color::Emerald)
            ->size('lg')
            ->visible(fn () => !($this->selectedAppeal?->status->isResolved() ?? true))
            ->schema([
                Textarea::make('reviewer_notes')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.reviewer_notes'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $appeal = $this->selectedAppeal;

                if (!$appeal) {
                    return;
                }

                try {
                    resolve(ReviewAppeal::class)->handle(
                        $appeal,
                        auth()->user(),
                        AppealStatus::Overturned,
                        $data['reviewer_notes'],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('panel-admin::moderation.appeal_queue.actions.overturned'))
                        ->send();

                    $this->advanceToNextAppeal();
                } catch (DomainException $domainException) {
                    Notification::make()
                        ->danger()
                        ->title($domainException->getMessage())
                        ->send();
                }
            });
    }

    public function render(): Factory|View
    {
        return view('panel-admin::moderation.appeal-queue.index');
    }

    private function resetSelection(): void
    {
        unset($this->appeals, $this->selectedAppeal);
        $this->selectedAppealId = $this->appeals->first()?->id;
    }

    private function advanceToNextAppeal(): void
    {
        unset($this->appeals, $this->selectedAppeal);

        $appeals = $this->appeals;
        $currentIndex = $appeals->search(fn (ModerationAppeal $a) => $a->id === $this->selectedAppealId);

        if ($currentIndex !== false && $appeals->has($currentIndex + 1)) {
            $this->selectedAppealId = $appeals->get($currentIndex + 1)->id;
        } else {
            $this->selectedAppealId = $appeals->first()?->id;
        }
    }
}
