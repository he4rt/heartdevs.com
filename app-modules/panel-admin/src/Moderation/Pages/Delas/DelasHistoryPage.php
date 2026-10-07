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
use Illuminate\Contracts\View\View;
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
        // Cada edição de motivo fica guardada como linha própria, mas aparece dentro
        // da linha que ela edita, não como uma linha a mais.
        $query = DelasTransition::query()
            ->with(['user', 'actor', 'corrections.actor'])
            ->where('action', '!=', DelasAction::ReasonCorrected);

        // A moderadora vê as decisões; a líder vê também os pedidos.
        if (!$this->isLead()) {
            $query->where('action', '!=', DelasAction::Requested);
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
                    ->action($this->viewVersionsAction())
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
                    ->options(collect(DelasAction::cases())
                        ->reject(fn (DelasAction $action): bool => $action === DelasAction::ReasonCorrected)
                        ->mapWithKeys(fn (DelasAction $action): array => [$action->value => $action->getLabel()])
                        ->all()),
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
     * Só a liderança abre as versões do motivo: a original e cada edição. Quem
     * edita pode ter tirado algo sensível, e isso não volta para a tela de todas.
     */
    private function viewVersionsAction(): Action
    {
        return Action::make('viewVersions')
            ->disabled(fn (DelasTransition $record): bool => !$this->isLead() || $record->corrections->isEmpty())
            ->modalHeading(__('panel-admin::delas.actions.versions_heading'))
            ->modalContent(fn (DelasTransition $record): View => view('panel-admin::moderation.delas.reason-versions', [
                'versions' => $record->corrections->prepend($record)->values(),
            ]))
            ->modalSubmitAction(action: false)
            ->modalCancelActionLabel(__('panel-admin::delas.actions.close'));
    }

    /**
     * "Editado em DD/MM/AA por X" na linha cujo motivo foi editado; a liderança
     * ganha também o "ver versões".
     */
    private function reasonNote(DelasTransition $record): ?HtmlString
    {
        $latest = $record->corrections->last();

        if (!$latest instanceof DelasTransition) {
            return null;
        }

        $note = e($this->text('panel-admin::delas.actions.corrected_note', [
            'date' => $latest->created_at?->timezone(config('app.display_timezone'))->format('d/m/y'),
            'name' => $latest->actor->name ?? '—',
        ]));

        if ($this->isLead()) {
            $note .= ' · <span style="text-decoration: underline; cursor: pointer">'.e($this->text('panel-admin::delas.actions.view_versions')).'</span>';
        }

        return new HtmlString($note);
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
