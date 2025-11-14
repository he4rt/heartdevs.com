<?php

declare(strict_types=1);

namespace He4rt\Core\Filament\Pages;

use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class He4rtPage extends Dashboard
{
    protected string $view = 'he4rt::3pontos.views.homepage';

    protected static bool $shouldRegisterNavigation = false;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
