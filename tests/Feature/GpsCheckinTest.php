<?php

declare(strict_types=1);

use He4rt\Events\Enums\CheckinStatusEnum;
use He4rt\Events\Enums\EventStatusEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;

use function Pest\Laravel\actingAs;

it('retorna 403 quando usuário não está inscrito', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create();

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -20.123,
        'lng' => -40.456,
    ]);

    $response->assertForbidden();

    $response->assertJson([
        'error' => 'not_registered',
    ]);
});

it('retorna 400 quando latitude e longitude são inválidas', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create();

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => null,
        'lng' => null,
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'valid_coordinates_required',
    ]);
});

it('retorna 400 quando o evento não está com scheduled', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create([
        'status' => null,
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -20.123,
        'lng' => -40.456,
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'event_not_scheduled',
    ]);
});

it('retorna 400 quando a janela de check-in ainda não abriu', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create([
        'status' => EventStatusEnum::Scheduled,
        'start_at' => now()->addHours(2),
        'end_at' => now()->addHours(4),
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -20.123,
        'lng' => -40.456,
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'event_not_started',
    ]);
});

it('retorna 400 quando a janela de check-in já fechou', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create([
        'status' => EventStatusEnum::Scheduled,
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -20.123,
        'lng' => -40.456,
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'event_already_finished',
    ]);
});

it('retorna 409 quando usuário já verificado', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create([
        'status' => EventStatusEnum::Scheduled,
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
        'state' => CheckinStatusEnum::Verified->value,
        'verified_at' => now(),
        'xp_awarded' => 100,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -20.123,
        'lng' => -40.456,
    ]);

    $response->assertStatus(409);

    $response->assertJsonStructure([
        'state',
        'verified_at',
        'xp_awarded',
    ]);
});

it('retorna 400 com distância quando fora do raio', function (): void {
    $user = User::factory()->create();

    $event = EventModel::factory()->create([
        'status' => EventStatusEnum::Scheduled,
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
        'location_lat' => -23.561684,
        'location_lng' => -46.655981,
        'gps_radius' => 100,
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -23.562684,
        'lng' => -46.655981,
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'outside_radius',
    ]);

    $response->assertJsonStructure([
        'error',
        'distance_meters',
        'radius_meters',
    ]);
});

it('retorna 200 quando dentro do raio e janela válida', function (): void {
    $user = User::factory()->create();

    Character::factory()->create([
        'user_id' => $user->id,
    ]);

    $event = EventModel::factory()->create([
        'status' => EventStatusEnum::Scheduled,
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
        'location_lat' => -23.561684,
        'location_lng' => -46.655981,
        'gps_radius' => 500,
        'xp_base' => 100,
    ]);

    $event->attendees()->attach($user->id, [
        'status' => 'attending',
        'attend_order' => 1,
    ]);

    $response = actingAs($user)->postJson(sprintf('/api/events/%s/checkin', $event->id), [
        'lat' => -23.561684,
        'lng' => -46.655981,
    ]);

    $response->assertOk();

    $response->assertJson([
        'state' => CheckinStatusEnum::Verified->value,
        'verification_method' => 'gps',
        'xp_awarded' => 100,
        'streak_multiplier' => 1,
        'streak_current' => 0,
    ]);

    $response->assertJsonStructure([
        'state',
        'verification_method',
        'verified_at',
        'xp_awarded',
        'streak_multiplier',
        'streak_current',
    ]);
});
