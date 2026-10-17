<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\Block\Actions\UnblockDelasRequester;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Bloqueios: desfaz um bloqueio, com motivo. A moderadora desfaz os próprios;
 * a líder, qualquer um.
 */
final class UnblockDelasRequesterAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.unblock'))
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('gray')
            ->button()
            ->size('sm')
            ->disabled(fn (DelasRequesterBlock $record): bool => !$this->canUnblock($record))
            ->tooltip(fn (DelasRequesterBlock $record): ?string => $this->canUnblock($record)
                ? null
                : $this->text('panel-admin::delas.actions.unblock_disabled'))
            ->modalHeading(fn (DelasRequesterBlock $record): string => $this->text('panel-admin::delas.actions.unblock_heading', [
                'name' => $record->user->name,
            ]))
            ->modalDescription(__('panel-admin::delas.actions.unblock_body'))
            ->modalSubmitActionLabel(__('panel-admin::delas.actions.unblock'))
            ->schema([
                $this->reasonField(__('panel-admin::delas.actions.unblock_reason'), hint: __('panel-admin::delas.history_hint')),
            ])
            ->action(fn (DelasRequesterBlock $record, array $data) => $this->runDomainAction(
                fn () => resolve(UnblockDelasRequester::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                $this->text('panel-admin::delas.actions.unblocked'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'unblock';
    }

    private function canUnblock(DelasRequesterBlock $block): bool
    {
        return $block->blocked_by === $this->actor()->getKey() || $this->isLead();
    }
}
