<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Actions\NumericCodeCheckInAction;
use He4rt\Events\CheckIn\DTOs\NumericCodeCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Events\ParticipantCheckedIn;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function (): void {
    Date::setTestNow(now()->setTime(12, 0));
});

afterEach(function (): void {
    Date::setTestNow();
});

/**
 * @return array<string, Enrollment|Collection<int, Enrollment>|Collection<int, CheckInCode>|CheckInCode|Event|Collection<int, Event>|User|Collection<int, User>>
 */
function createScenarioWithCode(array $codeOverrides = []): array
{
    $participant = User::factory()->create();
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $code = CheckInCode::factory()->create(array_merge([
        'event_id' => $event->id,
        'event_date' => now()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
        'max_uses' => null,
        'uses_count' => 0,
    ], $codeOverrides));

    return ['enrollment' => $enrollment, 'code' => $code, 'event' => $event, 'participant' => $participant];
}

test('when participant checks in with valid numeric code, then check-in is recorded and enrollment transitions', function (): void {
    EventFacade::fake([ParticipantCheckedIn::class]);

    $scenario = createScenarioWithCode();
    $enrollment = $scenario['enrollment'];
    $code = $scenario['code'];

    $checkIn = resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $enrollment,
            code: '123456',
            eventDate: now(),
        ),
    );

    expect($checkIn)->toBeInstanceOf(CheckIn::class)
        ->and($checkIn->enrollment_id)->toBe($enrollment->id)
        ->and($checkIn->method)->toBe(CheckInMethod::NumericCode)
        ->and($checkIn->event_date->isSameDay(now()))->toBeTrue()
        ->and($checkIn->checked_in_at)->not->toBeNull()
        ->and($checkIn->payload)->toBe(['code' => '123456'])
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($enrollment->fresh()->checked_in_at)->not->toBeNull();

    $code->refresh();
    expect($code->uses_count)->toBe(1);

    EventFacade::assertDispatched(fn (ParticipantCheckedIn $event): bool => $event->enrollmentId === $enrollment->id);
});

test('when code does not exist, then invalid check-in code error is thrown', function (): void {
    $scenario = createScenarioWithCode();

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '999999',
            eventDate: now(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.invalid_check_in_code'));
});

test('when code is for a different date, then wrong date error is thrown', function (): void {
    $scenario = createScenarioWithCode();

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: now()->addDay(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.check_in_code_wrong_date'));
});

test('when code is expired, then expired error is thrown', function (): void {
    $scenario = createScenarioWithCode([
        'expires_at' => now()->subDay(),
    ]);

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: now(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.check_in_code_expired'));
});

test('when code validity window has not started, then expired error is thrown', function (): void {
    $scenario = createScenarioWithCode([
        'starts_at' => now()->addHour(),
        'expires_at' => now()->addHours(2),
    ]);

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: now(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.check_in_code_expired'));
});

test('when code is revoked, then expired error is thrown', function (): void {
    $scenario = createScenarioWithCode([
        'revoked_at' => now(),
    ]);

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: now(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.check_in_code_expired'));
});

test('when code has reached max uses, then exhausted error is thrown', function (): void {
    $scenario = createScenarioWithCode([
        'max_uses' => 3,
        'uses_count' => 3,
    ]);

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: now(),
        ),
    ))->toThrow(fn (CheckInException $e): bool => $e->getMessage() === __('events::check_in.check_in_code_exhausted'));
});

test('uses_count is atomically incremented on each successful check-in', function (): void {
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    $participant1 = User::factory()->create();
    $enrollment1 = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $participant1->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $participant2 = User::factory()->create();
    $enrollment2 = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $participant2->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $code = CheckInCode::factory()->create([
        'event_id' => $event->id,
        'event_date' => now()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDay(),
        'max_uses' => 5,
    ]);

    resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $enrollment1,
            code: '123456',
            eventDate: now(),
        ),
    );

    expect($code->fresh()->uses_count)->toBe(1);

    resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $enrollment2,
            code: '123456',
            eventDate: now(),
        ),
    );

    expect($code->fresh()->uses_count)->toBe(2);
});

test('when same numeric code exists for multiple event dates, then current date code is used', function (): void {
    $participant = User::factory()->create();
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addDay()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $tomorrowCode = CheckInCode::factory()->create([
        'event_id' => $event->id,
        'event_date' => now()->addDay()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
    ]);

    $todayCode = CheckInCode::factory()->create([
        'event_id' => $event->id,
        'event_date' => now()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
    ]);

    resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $enrollment,
            code: '123456',
            eventDate: now(),
        ),
    );

    expect($todayCode->fresh()->uses_count)->toBe(1)
        ->and($tomorrowCode->fresh()->uses_count)->toBe(0);
});

test('when check-in date is outside event date range, then check-in is rejected by core action', function (): void {
    $startsAt = now()->setTime(9, 0);
    $scenario = createScenarioWithCode([
        'event_date' => $startsAt->clone()->addDays(2)->toDateString(),
    ]);
    $scenario['event']->update([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->setTime(18, 0),
    ]);

    expect(fn (): CheckIn => resolve(NumericCodeCheckInAction::class)->handle(
        new NumericCodeCheckInDTO(
            enrollment: $scenario['enrollment'],
            code: '123456',
            eventDate: $startsAt->clone()->addDays(2),
        ),
    ))->toThrow(CheckInException::class);
});
