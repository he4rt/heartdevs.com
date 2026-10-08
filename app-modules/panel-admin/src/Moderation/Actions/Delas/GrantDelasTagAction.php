<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\TagRequest\Actions\GrantDelasTag;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Concerns\InteractsWithDelasActions;
use He4rt\PanelAdmin\Moderation\Actions\Delas\Fields\DelasPersonSelect;

/**
 * Equipe: a líder dá a tag direto, sem fila e sem espera. Se a pessoa estiver
 * bloqueada, o modal mostra quem bloqueou, quando e por quê, e o motivo passa
 * a ser obrigatório, porque conceder encerra o bloqueio.
 */
final class GrantDelasTagAction extends Action
{
    use InteractsWithDelasActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::delas.actions.grant'))
            ->icon(Heroicon::OutlinedPlusCircle)
            ->modalHeading(__('panel-admin::delas.actions.grant_heading'))
            ->modalDescription(__('panel-admin::delas.actions.grant_body'))
            ->schema([
                // A comunidade inteira: só por busca, como nos outros seletores grandes do admin.
                DelasPersonSelect::make(
                    fn (DelasCandidates $candidates) => $candidates->forGrant($this->actor()),
                    opensWithList: false,
                )->live(),
                Callout::make(__('panel-admin::delas.actions.grant_blocked_heading'))
                    ->description(fn (Get $get): ?string => $this->blockSummary($this->activeBlockOf($get->string('user_id', isNullable: true))))
                    ->warning()
                    ->visible(fn (Get $get): bool => $this->activeBlockOf($get->string('user_id', isNullable: true)) instanceof DelasRequesterBlock),
                $this->reasonField(
                    __('panel-admin::delas.actions.grant_reason'),
                    required: fn (Get $get): bool => $this->activeBlockOf($get->string('user_id', isNullable: true)) instanceof DelasRequesterBlock,
                ),
            ])
            ->action(fn (array $data) => $this->runDomainAction(
                fn () => resolve(GrantDelasTag::class)->handle(DelasPersonSelect::chosen($data), $this->actor(), $this->reasonFrom($data)),
                $this->text('panel-admin::delas.actions.granted'),
            ));
    }

    public static function getDefaultName(): string
    {
        return 'grant';
    }

    private function activeBlockOf(?string $userId): ?DelasRequesterBlock
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        return DelasRequesterBlock::query()->active()->with('blocker')->where('user_id', $userId)->first();
    }

    private function blockSummary(?DelasRequesterBlock $block): ?string
    {
        if (!$block instanceof DelasRequesterBlock) {
            return null;
        }

        return $this->text('panel-admin::delas.actions.grant_blocked_body', [
            'name' => $block->blocker->name ?? '—',
            'date' => $block->blocked_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i'),
            'reason' => mb_rtrim($block->reason, '. '),
        ]);
    }
}
