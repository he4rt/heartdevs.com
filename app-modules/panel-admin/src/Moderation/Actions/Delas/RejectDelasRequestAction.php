<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Fila: rejeita o pedido com motivo. O modal já mostra quando a pessoa poderá
 * pedir de novo.
 */
final class RejectDelasRequestAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.reject'))
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->button()
            ->outlined()
            ->size('sm')
            ->modalHeading(fn (DelasTagRequest $record): string => $this->text('panel-admin::delas.actions.reject_heading', [
                'name' => $record->user->name,
            ]))
            ->modalDescription(fn (DelasTagRequest $record): string => $this->text('panel-admin::delas.actions.reject_body', [
                'name' => $record->user->name,
                'date' => $this->nextRequestDate(),
            ]))
            ->modalSubmitActionLabel(__('panel-admin::delas.actions.reject'))
            ->schema([$this->reasonField(__('panel-admin::delas.actions.reject_reason'))])
            ->action(fn (DelasTagRequest $record, array $data) => $this->runDomainAction(
                fn () => resolve(RejectDelasRequest::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                $this->text('panel-admin::delas.actions.rejected'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'reject';
    }

    /**
     * Rejeitando hoje, a partir de quando a pessoa pode pedir de novo.
     */
    private function nextRequestDate(): string
    {
        return now()
            ->addDays(config()->integer('delas.request_cooldown_days'))
            ->timezone(config('app.display_timezone'))
            ->format('d/m');
    }
}
