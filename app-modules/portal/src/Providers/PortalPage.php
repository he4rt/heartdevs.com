<?php

declare(strict_types=1);

namespace He4rt\Portal\Providers;

use Filament\Pages\Dashboard;

class PortalPage extends Dashboard
{
    protected string $view = 'portal::homepage';
}
