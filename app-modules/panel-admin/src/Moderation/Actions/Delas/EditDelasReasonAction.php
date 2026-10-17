<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\History\Actions\CorrectDelasReason;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Histórico: edita o motivo de uma decisão sem apagar o original. Quem escreveu
 * edita até o prazo; a líder, a qualquer momento. O campo já abre com o texto
 * atual todo selecionado: digitar substitui, clicar permite corrigir uma palavra.
 */
final class EditDelasReasonAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.correct_reason'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->button()
            ->size('sm')
            ->visible(fn (DelasTransition $record): bool => $this->canEdit($record))
            ->tooltip(fn (DelasTransition $record): string => $this->deadlineNote($record))
            ->modalHeading(__('panel-admin::delas.actions.correct_reason_heading'))
            ->modalDescription(fn (DelasTransition $record): string => implode(' ', [
                $this->text('panel-admin::delas.actions.correct_reason_body'),
                $this->deadlineNote($record),
            ]))
            ->fillForm(fn (DelasTransition $record): array => ['reason' => $record->currentReason()])
            ->schema([
                $this->reasonField(__('panel-admin::delas.actions.correct_reason_field'))
                    ->extraInputAttributes(['x-init' => '$nextTick(() => { $el.focus(); $el.select() })']),
            ])
            ->action(fn (DelasTransition $record, array $data) => $this->runDomainAction(
                fn () => resolve(CorrectDelasReason::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                $this->text('panel-admin::delas.actions.reason_corrected'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'correctReason';
    }

    /**
     * Mesma regra da action de domínio, só para mostrar o botão a quem pode.
     */
    private function canEdit(DelasTransition $record): bool
    {
        if (!$record->hasCorrectableReason()) {
            return false;
        }

        if ($this->isLead()) {
            return true;
        }

        $isAuthor = $record->actor_id === $this->actor()->getKey();

        return $isAuthor && ($record->reasonCorrectionDeadline()?->isFuture() ?? false);
    }

    private function deadlineNote(DelasTransition $record): string
    {
        if ($this->isLead()) {
            return $this->text('panel-admin::delas.actions.correct_anytime');
        }

        $deadline = $record->reasonCorrectionDeadline();

        return $this->text('panel-admin::delas.actions.correct_until', [
            'date' => $deadline?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—',
        ]);
    }
}
