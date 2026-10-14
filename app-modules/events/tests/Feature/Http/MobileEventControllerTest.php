<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Auth;

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/mobile/events')->assertUnauthorized();
});

it('lists only events viewable by participants, soonest first', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $draft = Event::factory()->create(['status' => EventStatus::Draft]);
    $sooner = Event::factory()->published()->create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(3)]);
    $later = Event::factory()->published()->create(['starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(3)]);

    $response = $this->getJson('/api/mobile/events', ['Authorization' => "Bearer {$token}"])
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->not->toContain($draft->id)
        ->and($ids->first())->toBe($sooner->id)
        ->and($ids->get(1))->toBe($later->id);
});

it('404s on an event not viewable by participants', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $draft = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->getJson("/api/mobile/events/{$draft->id}", ['Authorization' => "Bearer {$token}"])
        ->assertNotFound();
});

it('shows event detail with can_confirm_presence true for an open rsvp meetup', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asMeetup()->upcoming()->create();

    $this->getJson("/api/mobile/events/{$event->id}", ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.can_confirm_presence', true)
        ->assertJsonPath('data.enrollment', null);
});

it('enrolls via rsvp and confirms immediately when there is room', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asMeetup()->upcoming()->create();

    $this->postJson("/api/mobile/events/{$event->id}/enroll", [], ['Authorization' => "Bearer {$token}"])
        ->assertCreated()
        ->assertJsonPath('status', 'confirmed');

    $this->assertDatabaseHas('events_enrollments', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'confirmed',
    ]);
});

it('rejects a second enrollment for the same user', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asMeetup()->upcoming()->create();
    Enrollment::factory()->for($event)->for($user)->create(['status' => 'confirmed']);

    $this->postJson("/api/mobile/events/{$event->id}/enroll", [], ['Authorization' => "Bearer {$token}"])
        ->assertConflict()
        ->assertJsonStructure(['message']);
});

it('checks in with a valid numeric code', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asWorkshop()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(3)]);
    $enrollment = Enrollment::factory()->for($event)->for($user)->create([
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);
    $code = CheckInCode::factory()->create([
        'event_id' => $event->id,
        'event_date' => today(),
        'code' => '123456',
    ]);

    $this->postJson("/api/mobile/events/{$event->id}/check-in", ['code' => $code->code], ['Authorization' => "Bearer {$token}"])
        ->assertCreated()
        ->assertJsonStructure(['event_date', 'checked_in_at']);

    expect($enrollment->fresh()->status->value)->toBe('checked_in');
});

it('rejects an invalid check-in code format', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asWorkshop()->upcoming()->create();
    Enrollment::factory()->for($event)->for($user)->create(['status' => 'confirmed', 'confirmed_at' => now()]);

    $this->postJson("/api/mobile/events/{$event->id}/check-in", ['code' => 'abc'], ['Authorization' => "Bearer {$token}"])
        ->assertUnprocessable();
});

it('rejects check-in without an active enrollment', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asWorkshop()->upcoming()->create();
    $code = CheckInCode::factory()->create(['event_id' => $event->id, 'event_date' => today(), 'code' => '123456']);

    $this->postJson("/api/mobile/events/{$event->id}/check-in", ['code' => $code->code], ['Authorization' => "Bearer {$token}"])
        ->assertUnprocessable();
});

it('404s enrolling in an event not viewable by participants', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $draft = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->postJson("/api/mobile/events/{$draft->id}/enroll", [], ['Authorization' => "Bearer {$token}"])
        ->assertNotFound();
});

it('404s checking in on an event not viewable by participants', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $draft = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->postJson("/api/mobile/events/{$draft->id}/check-in", ['code' => '123456'], ['Authorization' => "Bearer {$token}"])
        ->assertNotFound();
});

it('rate limits check-in attempts after 5 tries', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $event = Event::factory()->asWorkshop()->upcoming()->create();
    Enrollment::factory()->for($event)->for($user)->create(['status' => 'confirmed', 'confirmed_at' => now()]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson("/api/mobile/events/{$event->id}/check-in", ['code' => '999999'], ['Authorization' => "Bearer {$token}"]);
    }

    $this->postJson("/api/mobile/events/{$event->id}/check-in", ['code' => '999999'], ['Authorization' => "Bearer {$token}"])
        ->assertTooManyRequests();
});
