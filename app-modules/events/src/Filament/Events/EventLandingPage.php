<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class EventLandingPage extends Dashboard
{
    protected static bool $shouldRegisterNavigation = false;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void {}

    public function getView(): string
    {

        if (app()->isLocal()) {
            $tenantSlug = str(request()->path())->explode('/')
                ->get(1);
        } else {
            $path = explode('.', request()->header('host'));
            $tenantSlug = array_shift($path);
        }

        $tenantSlug = str($tenantSlug)->replace(['.', '-'], '');

        $view = sprintf('events::components.themes.%s.homepage', $tenantSlug);

        abort_unless(view()->exists($view), 403, 'Forbidden Tenant');

        return $view;
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getLayout(): string
    {
        return 'he4rt::components.base.index';
    }
}
