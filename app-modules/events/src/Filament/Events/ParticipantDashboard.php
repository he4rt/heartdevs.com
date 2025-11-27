<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/** @property Tenant $tenant  */
class ParticipantDashboard extends Page
{
    protected Width|string|null $maxContentWidth = Width::Full;

    private ?Model $tenant = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function mount(): void
    {
        $this->tenant = filament()->getTenant();
    }

    public function getView(): string
    {
        $view = sprintf('events::components.themes.%s.participant-dashboard', $this->tenant->slug);

        abort_unless(view()->exists($view), 403, 'Forbidden Tenant');

        return $view;
    }

    protected function getViewData(): array
    {
        return ['event' => filament()->getTenant()->events()->first()];
    }
}
