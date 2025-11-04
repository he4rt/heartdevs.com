<?php

declare(strict_types=1);

use He4rt\Meeting\Models\MeetingType;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

test('bot can start new meeting', function (): void {
    // Arrange
    $providerName = 'discord';
    /** @var Tenant $tenant */
    $tenant = Tenant::factory()
        ->withDiscordProvider()
        ->create();

    /** @var Provider $provider */
    $provider = Provider::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => $providerName]);

    $meetingType = MeetingType::factory()->create();
    $payload = [
        'meeting_type_id' => $meetingType->getKey(),
        'provider_id' => $provider->provider_id,
    ];

    $expectedResponse = [
        'meeting_type_id' => $meetingType->getKey(),
        'admin_id' => $provider->model_id,
    ];

    // Act
    $response = $this
        ->actingAsAdmin()
        ->postJson(route('events.meeting.postMeeting', ['provider' => $providerName]), $payload);

    // Assert
    $response->assertStatus(Response::HTTP_CREATED)
        ->assertSee($expectedResponse);

    $this->assertDatabaseHas('meetings', $expectedResponse);
    expect(Cache::tags(['meetings'])->has('current-meeting'))->toBeTrue();
});

test('meeting type not found', function (): void {
    // Arrange
    $providerName = 'discord';

    /** @var Provider $provider */
    $provider = Provider::factory()->create(['provider' => $providerName]);

    $payload = [
        'meeting_type_id' => 12,
        'provider_id' => $provider->provider_id,
    ];

    // Act
    $response = $this
        ->actingAsAdmin()
        ->postJson(route('events.meeting.postMeeting', ['provider' => $providerName]), $payload);

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});
