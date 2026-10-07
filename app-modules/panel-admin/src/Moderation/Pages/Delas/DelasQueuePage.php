<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\TagRequest\Actions\ApproveDelasRequest;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;

/**
 * Fila de solicitações pendentes da tag He4rt Delas: aprovar, rejeitar ou
 * bloquear quem pediu.
 */
class DelasQueuePage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'delas/queue';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::delas.navigation.queue');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = DelasTagRequest::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.queue.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.queue.subtitle');
    }

    public function table(Table $table): Table
    {
        return $table
            // `user.roles`: o botão de bloquear checa o gate da pessoa em cada linha.
            ->query(DelasTagRequest::query()->pending()->with('user.roles'))
            ->defaultSort('requested_at', 'desc')
            ->emptyStateHeading(__('panel-admin::delas.empty.pending'))
            ->emptyStateDescription(__('panel-admin::delas.empty.pending_body'))
            ->emptyStateIcon(Heroicon::OutlinedInboxStack)
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-admin::delas.columns.requester')),
                $this->dateColumn('requested_at', __('panel-admin::delas.columns.requested_at')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('panel-admin::delas.actions.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.approve_heading', ['name' => $record->user->name]))
                    ->modalDescription(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.approve_body', ['username' => $record->user->username]))
                    ->action(fn (DelasTagRequest $record): bool => $this->attempt(
                        fn () => resolve(ApproveDelasRequest::class)->handle($record, $this->actor()),
                        __('panel-admin::delas.actions.approved'),
                    )),
                Action::make('reject')
                    ->label(__('panel-admin::delas.actions.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.reject_heading', ['name' => $record->user->name]))
                    ->modalDescription(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.reject_body', [
                        'name' => $record->user->name,
                        'date' => now()->addDays(config()->integer('delas.request_cooldown_days'))->timezone(config('app.display_timezone'))->format('d/m'),
                    ]))
                    ->modalSubmitActionLabel(__('panel-admin::delas.actions.reject'))
                    ->schema([$this->reasonField(__('panel-admin::delas.actions.reject_reason'))])
                    ->action(fn (DelasTagRequest $record, array $data): bool => $this->attempt(
                        fn () => resolve(RejectDelasRequest::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                        __('panel-admin::delas.actions.rejected'),
                    )),
                Action::make('block')
                    ->label(__('panel-admin::delas.actions.block'))
                    // Só o ícone, em vermelho; a dica explica o que bloquear faz (ou por que não dá).
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->iconButton()
                    ->color('danger')
                    ->size('sm')
                    ->disabled(fn (DelasTagRequest $record): bool => !$this->canBlock($record->user))
                    ->tooltip(fn (DelasTagRequest $record): string => $this->canBlock($record->user)
                        ? $this->text('panel-admin::delas.actions.block_tooltip')
                        : $this->text('panel-admin::delas.actions.block_disabled'))
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.block_heading', ['name' => $record->user->name]))
                    ->modalDescription(__('panel-admin::delas.actions.block_body'))
                    ->modalSubmitActionLabel(__('panel-admin::delas.actions.block'))
                    ->modalIcon(Heroicon::OutlinedNoSymbol)
                    ->modalIconColor('danger')
                    ->schema([$this->reasonField(__('panel-admin::delas.actions.block_reason'))])
                    ->action(fn (DelasTagRequest $record, array $data): bool => $this->attempt(
                        fn () => resolve(BlockDelasRequester::class)->handle($record->user, $this->actor(), $this->reasonFrom($data)),
                        __('panel-admin::delas.actions.blocked'),
                    )),
            ]);
    }

    private function canBlock(User $target): bool
    {
        return !$target->is($this->actor()) && !$target->can('moderate-delas');
    }
}
