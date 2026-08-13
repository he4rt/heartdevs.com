<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use He4rt\PanelAdmin\Agenda\AgendaCluster;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages\CreateUpcomingEvent;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages\EditUpcomingEvent;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages\ListUpcomingEvents;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Schemas\UpcomingEventForm;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Tables\UpcomingEventsTable;

class UpcomingEventResource extends Resource
{
    protected static ?string $cluster = AgendaCluster::class;

    protected static ?string $model = UpcomingEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'upcoming-events';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::agenda.navigation.upcoming_events');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::agenda.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::agenda.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::agenda.resource.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return UpcomingEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UpcomingEventsTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUpcomingEvents::route('/'),
            'create' => CreateUpcomingEvent::route('/create'),
            'edit' => EditUpcomingEvent::route('/{record}/edit'),
        ];
    }
}
