<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Fila: impede a pessoa de pedir a tag de novo, com motivo. Aparece só como o
 * cadeado vermelho; a dica explica o que bloquear faz, ou por que não dá.
 */
final class BlockDelasRequesterAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.block'))
            ->icon(Heroicon::OutlinedLockClosed)
            ->iconButton()
            ->color('danger')
            ->size('sm')
            ->disabled(fn (DelasTagRequest $record): bool => !$this->canBlock($record->user))
            ->tooltip(fn (DelasTagRequest $record): string => $this->canBlock($record->user)
                ? $this->text('panel-admin::delas.actions.block_tooltip')
                : $this->text('panel-admin::delas.actions.block_disabled'))
            ->modalHeading(fn (DelasTagRequest $record): string => $this->text('panel-admin::delas.actions.block_heading', [
                'name' => $record->user->name,
            ]))
            ->modalDescription(__('panel-admin::delas.actions.block_body'))
            ->modalSubmitActionLabel(__('panel-admin::delas.actions.block'))
            ->modalIcon(Heroicon::OutlinedNoSymbol)
            ->modalIconColor('danger')
            ->schema([$this->reasonField(__('panel-admin::delas.actions.block_reason'))])
            ->action(fn (DelasTagRequest $record, array $data) => $this->runDomainAction(
                fn () => resolve(BlockDelasRequester::class)->handle($record->user, $this->actor(), $this->reasonFrom($data)),
                $this->text('panel-admin::delas.actions.blocked'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'block';
    }

    /**
     * Mesma regra da action de domínio, só para desabilitar o botão: ninguém
     * bloqueia a si mesma nem quem modera.
     */
    private function canBlock(User $target): bool
    {
        return !$target->is($this->actor()) && !$target->can('moderate-delas');
    }
}
