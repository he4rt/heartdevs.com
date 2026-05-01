<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';
}
