<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Events;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Notifications\Notification;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Event $event
 * @property-read Enrollment|null $enrollment
 * @property-read QrToken|null $qrToken
 * @property-read string|null $qrCodeSvg
 * @property-read Collection<int, CheckIn> $checkIns
 * @property-read bool $hasCheckedInToday
 * @property-read bool $canConfirmPresence
 * @property-read bool $canApply
 * @property-read bool $isEventFull
 */
final class EventDetail extends Component
{
    public string $eventId;

    /** @var array<string, mixed> */
    public array $applicationFormData = [];

    public function mount(string $eventId): void
    {
        $this->eventId = $eventId;

        foreach ($this->event->enrollmentPolicy->application_schema ?? [] as $field) {
            if (($field['type'] ?? null) === 'checkbox' && isset($field['key'])) {
                $this->applicationFormData[(string) $field['key']] = [];
            }
        }
    }

    #[Computed]
    public function event(): Event
    {
        return Event::query()
            ->with('enrollmentPolicy')
            ->where('id', $this->eventId)
            ->viewableByParticipant()
            ->firstOrFail();
    }

    #[Computed]
    public function enrollment(): ?Enrollment
    {
        return Enrollment::query()
            ->with('qrToken')
            ->where('event_id', $this->eventId)
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function qrToken(): ?QrToken
    {
        if ($this->enrollment === null) {
            return null;
        }

        if (!in_array($this->enrollment->status, [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn], strict: true)) {
            return null;
        }

        if ($this->event->enrollmentPolicy?->check_in_method !== CheckInMethod::QrCode) {
            return null;
        }

        return $this->enrollment->qrToken;
    }

    #[Computed]
    public function qrCodeSvg(): ?string
    {
        if ($this->qrToken === null) {
            return null;
        }

        $writer = new Writer(new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd()));

        return $writer->writeString($this->qrToken->token);
    }

    /** @return Collection<int, CheckIn> */
    #[Computed]
    public function checkIns(): Collection
    {
        if ($this->enrollment === null) {
            return new Collection();
        }

        return CheckIn::query()
            ->where('enrollment_id', $this->enrollment->id)
            ->oldest('event_date')
            ->get();
    }

    #[Computed]
    public function hasCheckedInToday(): bool
    {
        return $this->checkIns->contains(
            fn (CheckIn $c): bool => $c->event_date->isToday(),
        );
    }

    #[Computed]
    public function canConfirmPresence(): bool
    {
        if ($this->event->status !== EventStatus::Published) {
            return false;
        }

        if ($this->enrollment !== null) {
            return false;
        }

        $policy = $this->event->enrollmentPolicy;

        if (!in_array($policy?->enrollment_method, [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin], strict: true)) {
            return false;
        }

        if (!$this->event->starts_at->isFuture()) {
            return false;
        }

        if ($policy->capacity === null) {
            return true;
        }

        $occupiedCount = Enrollment::query()
            ->where('event_id', $this->eventId)
            ->active()
            ->count();

        if ($occupiedCount < $policy->capacity) {
            return true;
        }

        return $policy->has_waitlist;
    }

    #[Computed]
    public function canApply(): bool
    {
        if ($this->event->status !== EventStatus::Published) {
            return false;
        }

        if ($this->enrollment !== null) {
            return false;
        }

        if ($this->event->enrollmentPolicy?->enrollment_method !== EnrollmentMethod::Application) {
            return false;
        }

        return $this->event->starts_at->isFuture();
    }

    #[Computed]
    public function isEventFull(): bool
    {
        if ($this->enrollment !== null) {
            return false;
        }

        $policy = $this->event->enrollmentPolicy;

        if ($policy?->capacity === null || $policy->has_waitlist) {
            return false;
        }

        $occupiedCount = Enrollment::query()
            ->where('event_id', $this->eventId)
            ->active()
            ->count();

        return $occupiedCount >= $policy->capacity;
    }

    public function confirmPresence(): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $enrollment = resolve(EnrollUserAction::class)->handle(
                EnrollUserDTO::fromModels($this->event, $user),
            );

            unset($this->enrollment, $this->canConfirmPresence, $this->canApply, $this->isEventFull);

            Notification::make()
                ->success()
                ->title($enrollment->status->getResponseMessage($enrollment->waitlist_position))
                ->send();
        } catch (EnrollmentException $enrollmentException) {
            Notification::make()
                ->danger()
                ->title($enrollmentException->getMessage())
                ->send();
        }
    }

    public function apply(): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            resolve(EnrollUserAction::class)->handle(
                new EnrollUserDTO(
                    eventId: $this->event->id,
                    userId: $user->id,
                    applicationData: $this->applicationFormData,
                ),
            );

            unset($this->enrollment, $this->canApply);
            $this->applicationFormData = [];

            Notification::make()
                ->success()
                ->title(__('events::pages.application_submitted'))
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
