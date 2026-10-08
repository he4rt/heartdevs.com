<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;
use Illuminate\Contracts\View\View;

/**
 * Histórico: clicar no motivo editado abre a linha do tempo das versões, da
 * original à que vale hoje. Só a liderança abre: quem edita pode ter tirado
 * algo sensível, e isso não volta para a tela de todas.
 */
final class ViewDelasReasonVersionsAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->disabled(fn (DelasTransition $record): bool => !$this->isLead() || $record->corrections->isEmpty())
            ->modalHeading(__('panel-admin::delas.actions.versions_heading'))
            ->modalContent(fn (DelasTransition $record): View => view('panel-admin::moderation.delas.reason-versions', [
                'versions' => $record->corrections->prepend($record)->values(),
            ]))
            ->modalSubmitAction(action: false)
            ->modalCancelActionLabel(__('panel-admin::delas.actions.close'));
    }

    public static function getDefaultName(): string
    {
        return 'viewVersions';
    }
}
