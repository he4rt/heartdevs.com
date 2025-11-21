<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ParticipantDashboard extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

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

        $view = sprintf('events::components.themes.%s.participant-dashboard', $tenantSlug);

        abort_unless(view()->exists($view), 403, 'Forbidden Tenant');

        return $view;
    }
}
