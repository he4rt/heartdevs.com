<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
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
        $query = DelasTransition::query()->with(['user', 'actor', 'corrected', 'latestCorrection.actor']);

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
                    ->description(fn (DelasTransition $record): ?string => $this->reasonNote($record))
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
                    ->modalDescription(__('panel-admin::delas.actions.correct_reason_body'))
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
     * Corrigir nunca esconde o que foi escrito: a linha corrigida mostra quando,
     * quem corrigiu e o texto original; a linha da correção mostra o que ela corrigiu.
     */
    private function reasonNote(DelasTransition $record): ?string
    {
        if ($record->corrected instanceof DelasTransition) {
            return $this->text('panel-admin::delas.actions.original_reason', ['reason' => $record->corrected->reason]);
        }

        $correction = $record->latestCorrection;

        if (!$correction instanceof DelasTransition) {
            return null;
        }

        return $this->text('panel-admin::delas.actions.corrected_note', [
            'date' => $correction->created_at?->timezone(config('app.display_timezone'))->format('d/m/y'),
            'name' => $correction->actor->name ?? '—',
        ]).' · '.$this->text('panel-admin::delas.actions.original_reason', ['reason' => $record->reason]);
    }
}
