<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\TagRequest\Actions\RevokeDelasTag;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Fields\DelasPersonSelect;

/**
 * Tira a tag de alguém, com motivo, e opcionalmente bloqueia novos pedidos no
 * mesmo passo. Na linha de Membras, a pessoa já é a da linha; no topo da
 * Equipe, a líder escolhe a pessoa.
 */
final class RevokeDelasTagAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.revoke'))
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('danger')
            ->outlined()
            // Na linha de Membras, quem é da equipe não tem o botão: sai da equipe antes.
            ->visible(fn (?DelasTagRequest $record): bool => $this->isLead() && !$this->isTeamMember($record?->user))
            ->modalHeading(fn (?DelasTagRequest $record): string => $record instanceof DelasTagRequest
                ? $this->text('panel-admin::delas.actions.revoke_member_heading', ['name' => $record->user->name])
                : $this->text('panel-admin::delas.actions.revoke_heading'))
            ->modalDescription(__('panel-admin::delas.actions.revoke_body'))
            ->modalSubmitActionLabel(__('panel-admin::delas.actions.revoke'))
            ->schema(fn (?DelasTagRequest $record): array => [
                ...($record instanceof DelasTagRequest ? [] : [$this->memberSelect()]),
                $this->reasonField(__('panel-admin::delas.actions.revoke_reason')),
                Toggle::make('also_block')
                    ->label(__('panel-admin::delas.actions.also_block'))
                    ->helperText(__('panel-admin::delas.actions.also_block_hint'))
                    ->default(state: false),
            ])
            ->action(function (?DelasTagRequest $record, array $data): void {
                $alsoBlock = ($data['also_block'] ?? false) === true;

                $this->runDomainAction(
                    fn () => resolve(RevokeDelasTag::class)->handle(
                        target: $record instanceof DelasTagRequest ? $record->user : DelasPersonSelect::chosen($data),
                        actor: $this->actor(),
                        reason: $this->reasonFrom($data),
                        alsoBlock: $alsoBlock,
                    ),
                    $this->text($alsoBlock ? 'panel-admin::delas.actions.revoked_and_blocked' : 'panel-admin::delas.actions.revoked'),
                );
            });
    }

    public static function getDefaultName(): string
    {
        return 'revoke';
    }

    private function isTeamMember(?User $person): bool
    {
        return $person?->hasAnyRole([UserRole::DelasModerator, UserRole::DelasLead]) ?? false;
    }

    private function memberSelect(): Select
    {
        return DelasPersonSelect::make(fn (DelasCandidates $candidates) => $candidates->forRevoke($this->actor()))
            ->helperText(__('panel-admin::delas.actions.member_hint'));
    }
}
