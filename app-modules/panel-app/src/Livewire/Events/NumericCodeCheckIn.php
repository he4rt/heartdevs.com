<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Events;

use Filament\Notifications\Notification;
use He4rt\Events\CheckIn\Actions\NumericCodeCheckInAction;
use He4rt\Events\CheckIn\DTOs\NumericCodeCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NumericCodeCheckIn extends Component
{
    public string $eventId;

    public string $code = '';

    public ?string $error = null;

    public function mount(string $eventId): void
    {
        $this->eventId = $eventId;
    }

    #[Computed]
    public function enrollment(): ?Enrollment
    {
        return Enrollment::query()
            ->with('event.enrollmentPolicy')
            ->where('event_id', $this->eventId)
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function canCheckIn(): bool
    {
        if ($this->enrollment === null) {
            return false;
        }

        if ($this->enrollment->event->enrollmentPolicy?->check_in_method !== CheckInMethod::NumericCode) {
            return false;
        }

        return in_array($this->enrollment->status, [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn], strict: true);
    }

    public function checkIn(): void
    {
        $this->error = null;
        $this->code = mb_trim($this->code);

        if ($this->enrollment === null || !$this->canCheckIn) {
            return;
        }

        $this->validate([
            'code' => ['required', 'string', 'regex:/^(?:\d{4}|\d{6})$/'],
        ], [
            'code.required' => CheckInException::invalidCheckInCodeFormat()->getMessage(),
            'code.regex' => CheckInException::invalidCheckInCodeFormat()->getMessage(),
        ]);

        $rateLimitKey = $this->rateLimitKey();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->error = CheckInException::checkInCodeRateLimited(
                RateLimiter::availableIn($rateLimitKey),
            )->getMessage();

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            resolve(NumericCodeCheckInAction::class)->handle(
                new NumericCodeCheckInDTO(
                    enrollment: $this->enrollment,
                    code: $this->code,
                    eventDate: Date::today(),
                ),
            );

            RateLimiter::clear($rateLimitKey);
            $this->code = '';

            Notification::make()
                ->success()
                ->title('Check-in confirmed!')
                ->send();
        } catch (CheckInException $checkInException) {
            $this->error = $checkInException->getMessage();
        }
    }

    public function render(): View
    {
        return view('panel-app::livewire.events.numeric-code-check-in');
    }

    private function rateLimitKey(): string
    {
        return sprintf(
            'numeric-code-check-in:%s:%s:%s',
            auth()->id() ?? 'guest',
            $this->eventId,
            request()->ip() ?? 'unknown',
        );
    }
}
