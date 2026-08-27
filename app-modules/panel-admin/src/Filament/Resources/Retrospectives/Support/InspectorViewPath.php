<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Portal\Retrospective\SlideView;

/**
 * O arquivo que o operador precisa abrir para mexer no que está selecionado.
 *
 * Só traduz a seleção para a convenção do portal (SlideView), que é dona do
 * mapeamento — o painel não recria o caminho, senão as duas telas poderiam
 * discordar sobre qual arquivo renderiza um slide.
 *
 * Bloco de fonte devolve null de propósito: uma fonte não tem view própria, ela
 * emite slides — e cada slide tem a sua.
 */
final class InspectorViewPath
{
    public static function for(InspectorSelection $selection): ?string
    {
        $view = match ($selection->mode) {
            InspectorMode::Cover => SlideView::cover(),
            InspectorMode::About => SlideView::about($selection->requireTarget()),
            InspectorMode::Closing => SlideView::closing(),
            InspectorMode::Slide => SlideView::kind($selection->requireTarget()),
            InspectorMode::Source => null,
        };

        return $view === null ? null : SlideView::path($view);
    }
}
