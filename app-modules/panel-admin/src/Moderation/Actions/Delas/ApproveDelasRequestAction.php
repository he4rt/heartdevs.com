<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\TagRequest\Actions\ApproveDelasRequest;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Fila: aprova o pedido, e a tag passa a aparecer no perfil da pessoa.
 */
final class ApproveDelasRequestAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.approve'))
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->button()
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (DelasTagRequest $record): string => $this->text('panel-admin::delas.actions.approve_heading', [
                'name' => $record->user->name,
            ]))
            ->modalDescription(fn (DelasTagRequest $record): string => $this->text('panel-admin::delas.actions.approve_body', [
                'username' => $record->user->username,
            ]))
            ->action(fn (DelasTagRequest $record) => $this->runDomainAction(
                fn () => resolve(ApproveDelasRequest::class)->handle($record, $this->actor()),
                $this->text('panel-admin::delas.actions.approved'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'approve';
    }
}
