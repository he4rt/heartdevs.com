<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Events;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Contracts\Support\Htmlable;

/** @property Tenant $tenant */
class ParticipantDashboard extends Page
{
    /** @var Tenant|null */
    public $tenant;

    /** @var EventModel|null */
    public $event;

    protected Width|string|null $maxContentWidth = Width::Full;

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

        $this->event = $this->tenant
            ->events()
            ->where('active', true)
            ->first();

        abort_unless($this->event, 404);
    }

    public function getView(): string
    {
        $view = sprintf('events::components.themes.%s.participant-dashboard', $this->tenant->slug);

        abort_unless(view()->exists($view), 403, 'Forbidden Tenant');

        return $view;
    }

    public function eventAttend(): void
    {

        $this->event->attend(auth()->id(), AttendingStatusEnum::Attending);
    }

    protected function getViewData(): array
    {
        return [
            'event' => $this->event,
            'participant' => $this->event->attendees()->where('user_id', auth()->id())->first(),
        ];
    }
}
