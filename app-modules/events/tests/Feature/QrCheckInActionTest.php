<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Actions\GenerateQrTokenAction;
use He4rt\Events\CheckIn\Actions\QrCheckInAction;
use He4rt\Events\CheckIn\DTOs\QrCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Events\ParticipantCheckedIn;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Str;

/**
 * @return array<int, Event|Collection<int, Event>|Enrollment|Collection<int, Enrollment>|QrToken>
 */
function createEnrollmentWithQrToken(array $eventAttributes = [], array $enrollmentAttributes = []): array
{
    $participant = User::factory()->create();
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ], $eventAttributes));

    $enrollment = Enrollment::factory()->create(array_merge([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ], $enrollmentAttributes));

    $qrToken = resolve(GenerateQrTokenAction::class)->handle($enrollment);

    return [$event, $enrollment, $qrToken];
}

test('when enrollment is confirmed, generate qr token action creates a unique token', function (): void {
    $enrollment = Enrollment::factory()->create([
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $qrToken = resolve(GenerateQrTokenAction::class)->handle($enrollment);

    expect($qrToken)->toBeInstanceOf(QrToken::class)
        ->and($qrToken->enrollment_id)->toBe($enrollment->id)
        ->and(Str::isUuid($qrToken->token))->toBeTrue()
        ->and($qrToken->expires_at)->toBeNull();
});

test('when generate qr token is called twice, the same token is returned', function (): void {
    $enrollment = Enrollment::factory()->create([
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $first = resolve(GenerateQrTokenAction::class)->handle($enrollment);
    $second = resolve(GenerateQrTokenAction::class)->handle($enrollment);

    expect($second->id)->toBe($first->id)
        ->and($second->token)->toBe($first->token);
});

test('when organizer scans a valid qr token, check-in is recorded and participant checked in event is dispatched', function (): void {
    EventFacade::fake([ParticipantCheckedIn::class]);

    [$event, $enrollment, $qrToken] = createEnrollmentWithQrToken();
    $organizer = User::factory()->create();

    $checkIn = resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: now(),
            actorUserId: $organizer->id,
        ),
    );

    expect($checkIn)->toBeInstanceOf(CheckIn::class)
        ->and($checkIn->enrollment_id)->toBe($enrollment->id)
        ->and($checkIn->method)->toBe(CheckInMethod::QrCode)
        ->and($checkIn->payload)->toBe(['token' => $qrToken->token])
        ->and($checkIn->event_date->isSameDay(now()))->toBeTrue()
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::CheckedIn);

    EventFacade::assertDispatched(fn (ParticipantCheckedIn $e): bool => $e->enrollmentId === $enrollment->id);
});

test('when token does not exist, qr check-in is rejected', function (): void {
    [$event] = createEnrollmentWithQrToken();

    expect(fn (): CheckIn => resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: 'nonexistent-token-that-does-not-exist-in-the-database-at-all',
            event: $event,
            eventDate: now(),
            actorUserId: User::factory()->create()->id,
        ),
    ))->toThrow(CheckInException::class);
});

test('when token belongs to a different event, qr check-in is rejected', function (): void {
    [, , $qrToken] = createEnrollmentWithQrToken();

    $startsAt = now()->setTime(9, 0);
    $otherEvent = Event::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->setTime(18, 0),
        'status' => EventStatus::Published,
    ]);

    expect(fn (): CheckIn => resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $otherEvent,
            eventDate: now(),
            actorUserId: User::factory()->create()->id,
        ),
    ))->toThrow(CheckInException::class);
});

test('when token is expired, qr check-in is rejected', function (): void {
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->setTime(18, 0),
        'status' => EventStatus::Published,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $expiredToken = QrToken::factory()->create([
        'enrollment_id' => $enrollment->id,
        'expires_at' => now()->subHour(),
    ]);

    expect(fn (): CheckIn => resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $expiredToken->token,
            event: $event,
            eventDate: now(),
            actorUserId: User::factory()->create()->id,
        ),
    ))->toThrow(CheckInException::class);
});

test('when duplicate scan on same day, qr check-in is rejected', function (): void {
    [$event, , $qrToken] = createEnrollmentWithQrToken();
    $organizer = User::factory()->create();

    resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: now(),
            actorUserId: $organizer->id,
        ),
    );

    expect(fn (): CheckIn => resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: now(),
            actorUserId: $organizer->id,
        ),
    ))->toThrow(CheckInException::class);
});

test('when cancelled enrollment token is scanned, qr check-in is rejected', function (): void {
    [$event, $enrollment, $qrToken] = createEnrollmentWithQrToken(
        enrollmentAttributes: ['status' => EnrollmentStatus::Cancelled, 'cancelled_at' => now()],
    );

    expect(fn (): CheckIn => resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: now(),
            actorUserId: User::factory()->create()->id,
        ),
    ))->toThrow(CheckInException::class);
});

test('when enrollment is confirmed via enroll user action, qr token is generated by listener', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(
            EnrollmentPolicy::factory()->rsvp()->state([
                'check_in_method' => CheckInMethod::QrCode,
            ]),
            'enrollmentPolicy',
        )
        ->create();

    $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect(QrToken::query()->where('enrollment_id', $enrollment->id)->exists())->toBeTrue();
});

test('when same token is scanned on different event days, each scan creates a new check-in record', function (): void {
    $startsAt = now()->setTime(9, 0);
    [$event, $enrollment, $qrToken] = createEnrollmentWithQrToken([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDay()->setTime(18, 0),
    ]);
    $organizer = User::factory()->create();

    resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: $startsAt,
            actorUserId: $organizer->id,
        ),
    );

    resolve(QrCheckInAction::class)->handle(
        new QrCheckInDTO(
            token: $qrToken->token,
            event: $event,
            eventDate: $startsAt->clone()->addDay(),
            actorUserId: $organizer->id,
        ),
    );

    expect(CheckIn::query()->where('enrollment_id', $enrollment->id)->count())->toBe(2);
});
