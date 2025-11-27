<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class EventLandingPage extends Dashboard
{
    public $tenant;

    protected static bool $shouldRegisterNavigation = false;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        $this->tenant = filament()->getTenant();
    }

    public function getView(): string
    {
        $view = sprintf('events::components.themes.%s.homepage', $this->tenant->slug);

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

    protected function getViewData(): array
    {
        return [
            'event' => $this->tenant->events()->first(),
        ];
    }
}
