<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Enums\BotCheckInStatus;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Events\CheckInProcessed;
use He4rt\Events\CheckIn\Events\CheckInRequested;
use He4rt\Events\CheckIn\Listeners\HandleBotCheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function (): void {
    Date::setTestNow(now()->setTime(12, 0));
});

afterEach(function (): void {
    Date::setTestNow();
});

function createBotCheckInScenario(array $codeOverrides = []): array
{
    $tenant = Tenant::factory()->create();
    $participant = User::factory()->create();
    $externalId = '900900900';
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->for($tenant)
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

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => $externalId,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $participant->id,
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

    return ['tenant' => $tenant, 'participant' => $participant, 'externalId' => $externalId, 'event' => $event, 'enrollment' => $enrollment, 'code' => $code];
}

test('dispatching CheckInRequested is handled by the registered listener', function (): void {
    $scenario = createBotCheckInScenario();

    event(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::CheckedIn);
    $this->assertDatabaseHas('events_check_ins', [
        'enrollment_id' => $scenario['enrollment']->id,
        'method' => CheckInMethod::NumericCode->value,
    ]);
});

test('successful bot check-in records the check-in and dispatches a success response', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
        channelContext: ['channel_id' => 'C1'],
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($scenario['code']->fresh()->uses_count)->toBe(1);

    $this->assertDatabaseHas('events_check_ins', [
        'enrollment_id' => $scenario['enrollment']->id,
        'method' => CheckInMethod::NumericCode->value,
    ]);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Success
        && $event->enrollmentId === $scenario['enrollment']->id
        && $event->externalUserId === $scenario['externalId']
        && $event->channelContext === ['channel_id' => 'C1']
        && $event->message === __('events::check_in.bot_check_in_success'));
});

test('bot check-in for an unlinked external user dispatches an error and records nothing', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: 'unlinked-user',
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::Confirmed);
    $this->assertDatabaseCount('events_check_ins', 0);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->enrollmentId === null
        && $event->message === __('events::check_in.bot_user_not_linked'));
});

test('bot check-in with an invalid code dispatches an error', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '000000',
        tenantId: $scenario['tenant']->id,
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::Confirmed);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.invalid_check_in_code'));
});

test('bot check-in for a user with no enrollment dispatches no active enrollment error', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();
    $scenario['enrollment']->delete();

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.bot_no_active_enrollment'));
});

test('bot check-in is rejected when the enrollment event is not happening today', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();
    $futureStart = now()->addDays(5)->setTime(9, 0);
    $scenario['event']->update([
        'starts_at' => $futureStart,
        'ends_at' => $futureStart->clone()->setTime(18, 0),
    ]);

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.bot_no_active_enrollment'));
});

test('bot check-in with multiple events today checks into the one matching the typed code', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    $afternoonStart = now()->setTime(14, 0);
    $afternoonEvent = Event::factory()
        ->for($scenario['tenant'])
        ->create([
            'starts_at' => $afternoonStart,
            'ends_at' => $afternoonStart->clone()->setTime(20, 0),
            'status' => EventStatus::Published,
        ]);

    $afternoonEnrollment = Enrollment::factory()->create([
        'event_id' => $afternoonEvent->id,
        'user_id' => $scenario['participant']->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    CheckInCode::factory()->create([
        'event_id' => $afternoonEvent->id,
        'event_date' => now()->toDateString(),
        'code' => '654321',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(8),
    ]);

    // User types the MORNING code — only that event owns it.
    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($afternoonEnrollment->fresh()->status)->toBe(EnrollmentStatus::Confirmed);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Success
        && $event->enrollmentId === $scenario['enrollment']->id);
});

test('bot check-in with multiple events today and a non-matching code errors as invalid', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    $startsAt = now()->setTime(9, 0);
    $secondEvent = Event::factory()
        ->for($scenario['tenant'])
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    Enrollment::factory()->create([
        'event_id' => $secondEvent->id,
        'user_id' => $scenario['participant']->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    CheckInCode::factory()->create([
        'event_id' => $secondEvent->id,
        'event_date' => now()->toDateString(),
        'code' => '654321',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
    ]);

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '999999',
        tenantId: $scenario['tenant']->id,
    ));

    expect($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::Confirmed);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.invalid_check_in_code'));
});

test('bot check-in errors when the same code exists on multiple events today', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    $startsAt = now()->setTime(9, 0);
    $secondEvent = Event::factory()
        ->for($scenario['tenant'])
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    Enrollment::factory()->create([
        'event_id' => $secondEvent->id,
        'user_id' => $scenario['participant']->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    CheckInCode::factory()->create([
        'event_id' => $secondEvent->id,
        'event_date' => now()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
    ]);

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.bot_multiple_active_events'));
});

test('bot check-in when already checked in today dispatches an error', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();

    $request = new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    );

    resolve(HandleBotCheckIn::class)->handle($request);
    resolve(HandleBotCheckIn::class)->handle($request);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Error
        && $event->message === __('events::check_in.already_checked_in_for_date'));
});

test('bot check-in ignores a waitlisted enrollment and checks in the confirmed one', function (): void {
    EventFacade::fake([CheckInProcessed::class]);
    $scenario = createBotCheckInScenario();
    $scenario['enrollment']->update(['status' => EnrollmentStatus::Waitlisted]);

    $startsAt = now()->setTime(9, 0);
    $confirmedEvent = Event::factory()
        ->for($scenario['tenant'])
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);

    $confirmedEnrollment = Enrollment::factory()->create([
        'event_id' => $confirmedEvent->id,
        'user_id' => $scenario['participant']->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    CheckInCode::factory()->create([
        'event_id' => $confirmedEvent->id,
        'event_date' => now()->toDateString(),
        'code' => '123456',
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHours(2),
    ]);

    resolve(HandleBotCheckIn::class)->handle(new CheckInRequested(
        provider: IdentityProvider::Discord,
        externalUserId: $scenario['externalId'],
        code: '123456',
        tenantId: $scenario['tenant']->id,
    ));

    expect($confirmedEnrollment->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($scenario['enrollment']->fresh()->status)->toBe(EnrollmentStatus::Waitlisted);

    EventFacade::assertDispatched(fn (CheckInProcessed $event): bool => $event->status === BotCheckInStatus::Success
        && $event->enrollmentId === $confirmedEnrollment->id);
});
