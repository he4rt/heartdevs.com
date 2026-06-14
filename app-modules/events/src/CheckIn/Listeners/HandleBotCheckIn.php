<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Listeners;

use He4rt\Events\CheckIn\Actions\NumericCodeCheckInAction;
use He4rt\Events\CheckIn\DTOs\NumericCodeCheckInDTO;
use He4rt\Events\CheckIn\Enums\BotCheckInStatus;
use He4rt\Events\CheckIn\Events\CheckInProcessed;
use He4rt\Events\CheckIn\Events\CheckInRequested;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Exceptions\ExternalIdentityException;
use Illuminate\Database\Eloquent\Builder;

final readonly class HandleBotCheckIn
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
        private NumericCodeCheckInAction $numericCodeCheckIn,
    ) {}

    public function handle(CheckInRequested $event): void
    {
        try {
            $userId = $this->resolveUserId($event);
            $enrollment = $this->resolveEnrollment($event, $userId);

            $checkIn = $this->numericCodeCheckIn->handle(
                new NumericCodeCheckInDTO(
                    enrollment: $enrollment,
                    code: $event->code,
                    eventDate: now(),
                ),
            );

            $this->dispatchSuccess($event, $checkIn->enrollment_id);
        } catch (ExternalIdentityException) {
            $this->dispatchError($event, __('events::check_in.bot_user_not_linked'));
        } catch (CheckInException $exception) {
            $this->dispatchError($event, $exception->getMessage());
        }
    }

    private function resolveUserId(CheckInRequested $event): string
    {
        return $this->findExternalIdentity->handle(
            provider: $event->provider->value,
            providerId: $event->externalUserId,
            tenantId: $event->tenantId,
        )->model_id;
    }

    private function resolveEnrollment(CheckInRequested $event, string $userId): Enrollment
    {
        $today = now()->toDateString();

        $enrollments = Enrollment::query()
            ->where('user_id', $userId)
            ->whereIn('status', [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn])
            ->whereHas('event', function (Builder $query) use ($event, $today): void {
                $query->where('tenant_id', $event->tenantId)
                    ->where('status', EventStatus::Published)
                    ->whereDate('starts_at', '<=', $today)
                    ->whereDate('ends_at', '>=', $today);
            })
            ->get();

        $enrollment = $enrollments->first();

        throw_unless($enrollment, CheckInException::botNoActiveEnrollment());
        throw_if($enrollments->count() > 1, CheckInException::botMultipleActiveEvents());

        return $enrollment;
    }

    private function dispatchSuccess(CheckInRequested $event, string $enrollmentId): void
    {
        event(new CheckInProcessed(
            provider: $event->provider,
            externalUserId: $event->externalUserId,
            status: BotCheckInStatus::Success,
            message: __('events::check_in.bot_check_in_success'),
            channelContext: $event->channelContext,
            enrollmentId: $enrollmentId,
        ));
    }

    private function dispatchError(CheckInRequested $event, string $message): void
    {
        event(new CheckInProcessed(
            provider: $event->provider,
            externalUserId: $event->externalUserId,
            status: BotCheckInStatus::Error,
            message: $message,
            channelContext: $event->channelContext,
        ));
    }
}
