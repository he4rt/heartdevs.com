<?php

declare(strict_types=1);

namespace He4rt\Portal\Providers;

use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class PortalPage extends Dashboard
{
    protected string $view = 'portal::homepage';

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
