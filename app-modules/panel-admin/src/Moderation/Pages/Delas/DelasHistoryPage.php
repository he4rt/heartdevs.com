<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\PanelAdmin\Moderation\Actions\Delas\EditDelasReasonAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\ViewDelasReasonVersionsAction;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;
use Illuminate\Database\Eloquent\Builder;
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
        return $table
            ->query($this->visibleHistory())
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
                    ->description(fn (DelasTransition $record): ?HtmlString => $this->editedNote($record))
                    ->action(ViewDelasReasonVersionsAction::make())
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditDelasReasonAction::make(),
            ])
            ->filters($this->isLead() ? [$this->actionFilter()] : []);
    }

    /**
     * Cada edição de motivo fica guardada como linha própria, mas aparece dentro
     * da linha que ela edita. A moderadora vê as decisões; a líder vê também os
     * pedidos (os pedidos em aberto a moderadora já vê na Fila).
     *
     * @return Builder<DelasTransition>
     */
    private function visibleHistory(): Builder
    {
        $hidden = $this->isLead()
            ? [DelasAction::ReasonCorrected]
            : [DelasAction::ReasonCorrected, DelasAction::Requested];

        return DelasTransition::query()
            ->with(['user', 'actor', 'corrections.actor'])
            ->whereNotIn('action', $hidden);
    }

    private function actionFilter(): SelectFilter
    {
        $actions = array_filter(DelasAction::cases(), fn (DelasAction $action): bool => $action !== DelasAction::ReasonCorrected);

        return SelectFilter::make('action')
            ->label(__('panel-admin::delas.columns.action'))
            ->options(collect($actions)->mapWithKeys(fn (DelasAction $action): array => [$action->value => $action->getLabel()])->all());
    }

    /**
     * "Editado em DD/MM/AA por X" na linha cujo motivo foi editado; a liderança
     * ganha também o "ver versões", que abre ao clicar no motivo.
     */
    private function editedNote(DelasTransition $record): ?HtmlString
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
            $note .= ' · '.$this->clickableHint('panel-admin::delas.actions.view_versions');
        }

        return new HtmlString($note);
    }
}
