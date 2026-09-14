<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Page;

class EventsPage extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Events';

    protected static ?string $title = 'Events';

    protected static ?int $navigationSort = 2;

    protected string $view = 'panel-app::pages.events';
}
