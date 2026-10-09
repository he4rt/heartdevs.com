<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;

/**
 * Equipe: tira o papel de moderadora. Líderes só são atribuídas e retiradas
 * por super admins, no cadastro de usuários.
 */
final class RemoveDelasModeratorAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.remove_moderator'))
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->button()
            ->outlined()
            ->size('sm')
            ->disabled(fn (User $record): bool => $record->hasRole(UserRole::DelasLead))
            ->tooltip(fn (User $record): ?string => $record->hasRole(UserRole::DelasLead)
                ? $this->text('panel-admin::delas.actions.lead_only')
                : null)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => $this->text('panel-admin::delas.actions.remove_moderator_heading', ['name' => $record->name]))
            ->modalDescription(fn (User $record): string => $this->text('panel-admin::delas.actions.remove_moderator_body', ['name' => $record->name]))
            ->action(fn (User $record) => $this->runDomainAction(
                fn () => resolve(RemoveDelasModerator::class)->handle($record, $this->actor()),
                $this->text('panel-admin::delas.actions.moderator_removed'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'removeModerator';
    }
}
