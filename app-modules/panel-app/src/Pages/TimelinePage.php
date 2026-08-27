<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;

class TimelinePage extends BaseDashboard
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Timeline';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'panel-app::dashboard';

}
