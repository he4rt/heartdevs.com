<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\Url;

class TimelinePage extends BaseDashboard
{
    #[Url]
    public string $variant = 'A';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Timeline';

    protected string $view = 'panel-app::dashboard';
}
