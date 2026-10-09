<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Fields\DelasPersonSelect;

/**
 * Equipe: a líder dá o papel de moderadora a uma membra que já tem a tag.
 */
final class AddDelasModeratorAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.add_moderator'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalHeading(__('panel-admin::delas.actions.add_moderator_heading'))
            ->modalDescription(__('panel-admin::delas.actions.add_moderator_body'))
            ->schema([
                DelasPersonSelect::make(fn (DelasCandidates $candidates) => $candidates->forModerator($this->actor()))
                    ->helperText(__('panel-admin::delas.actions.moderator_hint')),
            ])
            ->action(fn (array $data) => $this->runDomainAction(
                fn () => resolve(AddDelasModerator::class)->handle(DelasPersonSelect::chosen($data), $this->actor()),
                $this->text('panel-admin::delas.actions.moderator_added'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'addModerator';
    }
}
