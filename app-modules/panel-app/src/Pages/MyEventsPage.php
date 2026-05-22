<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Page;

class MyEventsPage extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'My Events';

    protected static ?string $title = 'My Events';

    protected static ?int $navigationSort = 3;

    protected string $view = 'panel-app::pages.my-events';
}
