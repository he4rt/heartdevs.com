<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Events;

use Filament\Notifications\Notification;
use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class EventDetail extends Component
{
    public string $eventId;

    public function mount(string $eventId): void
    {
        $this->eventId = $eventId;
    }

    #[Computed]
    public function event(): Event
    {
        return Event::query()
            ->with('enrollmentPolicy')
            ->where('id', $this->eventId)
            ->where('tenant_id', filament()->getTenant()->getKey())
            ->where('status', EventStatus::Published)
            ->firstOrFail();
    }

    #[Computed]
    public function enrollment(): ?Enrollment
    {
        return Enrollment::query()
            ->where('event_id', $this->eventId)
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function canConfirmPresence(): bool
    {
        if ($this->enrollment !== null) {
            return false;
        }

        $method = $this->event->enrollmentPolicy?->enrollment_method;

        if (!in_array($method, [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin], strict: true)) {
            return false;
        }

        return $this->event->starts_at->isFuture();
    }

    public function confirmPresence(): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            resolve(EnrollUserAction::class)->handle(
                EnrollUserDTO::fromModels($this->event, $user),
            );

            unset($this->enrollment, $this->canConfirmPresence);

            Notification::make()
                ->success()
                ->title(__('events::pages.confirm_presence_success'))
                ->send();
        } catch (EnrollmentException $enrollmentException) {
            Notification::make()
                ->danger()
                ->title($enrollmentException->getMessage())
                ->send();
        }
    }

    public function render(): View
    {
        return view('panel-app::livewire.events.event-detail');
    }
}
