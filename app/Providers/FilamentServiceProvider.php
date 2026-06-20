<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\FilamentPanel;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Panel;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureFlux();
        $this->configureField();
        $this->configureColumn();
        $this->configureIconColumn();
        $this->configureCheckboxColumn();
        $this->configureImageColumn();
        $this->configureSelect();
        $this->configureDateTimePicker();
        $this->configureRepeater();
        $this->configureBuilder();
        $this->configureSelectFilter();
        $this->configureTable();
    }

    public function register(): void
    {
        $this->configureMacros();
    }

    private function configureMacros(): void
    {
        Panel::macro('currentPanel', function (): FilamentPanel {
            /** @var string $panelId */
            $panelId = $this->getId();

            return FilamentPanel::from($panelId);
        });
    }

    private function configureFlux(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): View => view('flux.flux-styles'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): View => view('flux.flux-scripts'),
        );
    }

    private function configureField(): void
    {
        Field::configureUsing(fn (Field $field): Field => $field
            ->translateLabel());
    }

    private function configureColumn(): void
    {
        Column::configureUsing(fn (Column $column): Column => $column
            ->translateLabel());
    }

    private function configureIconColumn(): void
    {
        IconColumn::configureUsing(fn (IconColumn $iconColumn): IconColumn => $iconColumn
            ->alignment(Alignment::Center)
            ->verticalAlignment(VerticalAlignment::Center));
    }

    private function configureCheckboxColumn(): void
    {
        CheckboxColumn::configureUsing(fn (CheckboxColumn $checkboxColumn): CheckboxColumn => $checkboxColumn
            ->alignment(Alignment::Center)
            ->verticalAlignment(VerticalAlignment::Center));
    }

    private function configureImageColumn(): void
    {
        ImageColumn::configureUsing(fn (ImageColumn $imageColumn): ImageColumn => $imageColumn
            ->extraImgAttributes(['loading' => 'lazy']));
    }

    private function configureSelectFilter(): void
    {
        SelectFilter::configureUsing(fn (SelectFilter $selectFilter): SelectFilter => $selectFilter
            ->native(condition: false));
    }

    private function configureTable(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table
            ->deferLoading()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->extremePaginationLinks()
            ->paginationMode(PaginationMode::Cursor)
            ->defaultPaginationPageOption(10)
            ->filtersFormWidth(Width::Medium)
            ->paginated([10, 25, 50])
            ->emptyStateIcon(Heroicon::OutlinedExclamationTriangle));
    }

    private function configureSelect(): void
    {
        Select::configureUsing(fn (Select $select): Select => $select
            ->native(condition: false)
            ->selectablePlaceholder(fn (Select $component) => !$component->isRequired())
            ->searchable(fn (Select $component) => $component->hasRelationship())
            ->preload(fn (Select $component): bool => $component->isSearchable() && !$component->hasRelationship())
            ->translateLabel());
    }

    private function configureDateTimePicker(): void
    {
        DateTimePicker::configureUsing(fn (DateTimePicker $dateTimePicker): DateTimePicker => $dateTimePicker
            ->native(condition: false)
            ->seconds(condition: false)
            ->minDate(now()->subYears(25))
            ->maxDate(now()->addYears(25))
            ->translateLabel());
    }

    private function configureRepeater(): void
    {
        Repeater::configureUsing(fn (Repeater $component): Repeater => $component
            ->deleteAction(fn (Action $action): Action => $action->requiresConfirmation()));
    }

    private function configureBuilder(): void
    {
        Builder::configureUsing(fn (Builder $component): Builder => $component
            ->deleteAction(fn (Action $action): Action => $action->requiresConfirmation()));
    }
}
