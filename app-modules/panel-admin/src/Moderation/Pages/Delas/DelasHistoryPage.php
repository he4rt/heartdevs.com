<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Delas\History\Actions\CorrectDelasReason;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;
use Illuminate\Support\HtmlString;

/**
 * Histórico das decisões sobre a tag He4rt Delas. A moderadora vê as decisões;
 * a líder vê tudo, inclusive os pedidos, e filtra por ação.
 */
class DelasHistoryPage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 23;

    protected static ?string $slug = 'delas/history';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->can('lead-delas')
            ? __('panel-admin::delas.navigation.history_full')
            : __('panel-admin::delas.navigation.history');
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.history.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.history.subtitle');
    }

    public function table(Table $table): Table
    {
        $query = DelasTransition::query()->with(['user', 'actor', 'corrected.actor', 'latestCorrection.actor']);

        // A moderadora vê as decisões; a líder vê também os pedidos e cada correção de motivo.
        if (!$this->isLead()) {
            $query->whereNotIn('action', [DelasAction::Requested, DelasAction::ReasonCorrected]);
        }

        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('panel-admin::delas.empty.history'))
            ->emptyStateDescription(__('panel-admin::delas.empty.history_body'))
            ->emptyStateIcon(Heroicon::OutlinedClock)
            ->columns([
                $this->dateColumn('created_at', __('panel-admin::delas.columns.date')),
                TextColumn::make('action')
                    ->label(__('panel-admin::delas.columns.action'))
                    ->badge(),
                $this->personColumn('user.name', 'user.username', __('panel-admin::delas.columns.person')),
                TextColumn::make('actor.name')
                    ->label(__('panel-admin::delas.columns.actor'))
                    ->description(fn (DelasTransition $record): string => $record->triggered_by->getLabel())
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->label(__('panel-admin::delas.columns.reason'))
                    ->state(fn (DelasTransition $record): ?string => $record->currentReason())
                    ->description(fn (DelasTransition $record): ?HtmlString => $this->reasonNote($record))
                    ->action($this->viewOriginalAction())
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('correctReason')
                    ->label(__('panel-admin::delas.actions.correct_reason'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->visible(fn (DelasTransition $record): bool => $this->canCorrect($record))
                    ->modalHeading(__('panel-admin::delas.actions.correct_reason_heading'))
                    ->tooltip(fn (DelasTransition $record): string => $this->correctionWindowNote($record))
                    ->modalDescription(fn (DelasTransition $record): string => $this->text('panel-admin::delas.actions.correct_reason_body').' '.$this->correctionWindowNote($record))
                    ->fillForm(fn (DelasTransition $record): array => ['reason' => $record->currentReason()])
                    ->schema([$this->reasonField(__('panel-admin::delas.actions.correct_reason_field'))])
                    ->action(fn (DelasTransition $record, array $data): bool => $this->attempt(
                        fn () => resolve(CorrectDelasReason::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                        __('panel-admin::delas.actions.reason_corrected'),
                    )),
            ])
            ->filters($this->isLead() ? [
                SelectFilter::make('action')
                    ->label(__('panel-admin::delas.columns.action'))
                    ->options(DelasAction::class),
            ] : []);
    }

    /**
     * Mesma regra da action de domínio, só para mostrar o botão a quem pode:
     * a líder sempre; quem escreveu, dentro do prazo.
     */
    private function canCorrect(DelasTransition $record): bool
    {
        if (!$record->hasCorrectableReason()) {
            return false;
        }

        if ($this->isLead()) {
            return true;
        }

        return $record->actor_id === $this->actor()->getKey()
            && ($record->created_at?->greaterThanOrEqualTo(now()->subHours(config()->integer('delas.reason_correction_hours'))) ?? false);
    }

    /**
     * Clicar no motivo corrigido abre o texto original, com quem escreveu e quando.
     */
    private function viewOriginalAction(): Action
    {
        return Action::make('viewOriginal')
            ->disabled(fn (DelasTransition $record): bool => !$this->originalOf($record) instanceof DelasTransition)
            ->modalHeading(__('panel-admin::delas.actions.original_heading'))
            ->modalDescription(function (DelasTransition $record): ?string {
                $original = $this->originalOf($record);

                return $original instanceof DelasTransition ? $this->text('panel-admin::delas.actions.original_written', [
                    'name' => $original->actor->name ?? '—',
                    'date' => $this->displayDate($original->created_at),
                ]) : null;
            })
            ->modalContent(fn (DelasTransition $record): HtmlString => new HtmlString(
                '<p style="white-space: pre-line"><span style="opacity: .7">'.e($this->text('panel-admin::delas.actions.message_label')).'</span> '.e($this->originalOf($record)?->reason).'</p>',
            ))
            ->modalSubmitAction(action: false)
            ->modalCancelActionLabel(__('panel-admin::delas.actions.close'));
    }

    /**
     * A decisão cujo motivo foi corrigido: a própria linha, se já tem correção,
     * ou a linha que a correção aponta.
     */
    private function originalOf(DelasTransition $record): ?DelasTransition
    {
        if ($record->corrected instanceof DelasTransition) {
            return $record->corrected;
        }

        return $record->latestCorrection instanceof DelasTransition ? $record : null;
    }

    /**
     * "Corrigido em DD/MM/AA por X · ver original" na linha corrigida; só
     * "ver original" na linha da correção.
     */
    private function reasonNote(DelasTransition $record): ?HtmlString
    {
        if (!$this->originalOf($record) instanceof DelasTransition) {
            return null;
        }

        $link = '<span style="text-decoration: underline; cursor: pointer">'.e($this->text('panel-admin::delas.actions.view_original')).'</span>';
        $correction = $record->latestCorrection;

        if (!$correction instanceof DelasTransition) {
            return new HtmlString($link);
        }

        return new HtmlString(e($this->text('panel-admin::delas.actions.corrected_note', [
            'date' => $correction->created_at?->timezone(config('app.display_timezone'))->format('d/m/y'),
            'name' => $correction->actor->name ?? '—',
        ])).' · '.$link);
    }

    /**
     * Até quando quem escreveu pode corrigir; a líder corrige sempre.
     */
    private function correctionWindowNote(DelasTransition $record): string
    {
        if ($this->isLead()) {
            return $this->text('panel-admin::delas.actions.correct_anytime');
        }

        return $this->text('panel-admin::delas.actions.correct_until', [
            'date' => $this->displayDate($record->created_at?->copy()->addHours(config()->integer('delas.reason_correction_hours'))),
        ]);
    }

    private function displayDate(?CarbonInterface $date): string
    {
        return $date?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—';
    }
}
